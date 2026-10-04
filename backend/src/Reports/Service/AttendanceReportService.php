<?php

declare(strict_types=1);

namespace App\Reports\Service;

use App\Auth\CurrentUser;
use App\Reports\Model\ReportViewModel;
use App\Reports\Renderer\ReportRenderer;
use App\Reports\Repository\ReportRepository;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;

final class AttendanceReportService
{
    public function __construct(private ReportRepository $repository, private ReportRenderer $renderer)
    {
    }

    public function options(CurrentUser $user): array
    {
        if (!in_array($user->role, ['admin', 'teacher'], true)) {
            throw new DomainException('Forbidden.');
        }
        return $this->repository->options($user->schoolId, $user->role, $user->id);
    }

    public function generate(CurrentUser $user, array $query): array
    {
        $groupId = filter_var($query['group_id'] ?? null, FILTER_VALIDATE_INT);
        $days = filter_var($query['days'] ?? null, FILTER_VALIDATE_INT);
        $subjectId = isset($query['subject_id']) && $query['subject_id'] !== ''
            ? filter_var($query['subject_id'], FILTER_VALIDATE_INT)
            : null;
        $startDate = $query['start_date'] ?? null;
        if (!in_array($user->role, ['admin', 'teacher'], true)) {
            throw new DomainException('Forbidden.');
        }
        if ($groupId === false || $groupId < 1 || $days === false || $days < 1 || $days > 14
            || ($subjectId !== null && ($subjectId === false || $subjectId < 1))
            || !is_string($startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)
            || !($date = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate, new DateTimeZone('UTC')))
            || $date->format('Y-m-d') !== $startDate
        ) {
            throw new \InvalidArgumentException('Invalid attendance report parameters.');
        }
        $group = $this->repository->group($user->schoolId, $groupId);
        if ($group === null) {
            throw new DomainException('Group not found.');
        }
        $subject = $subjectId === null ? null : $this->repository->subject($user->schoolId, $subjectId);
        if ($subjectId !== null && $subject === null) {
            throw new DomainException('Subject not found.');
        }
        if ($subjectId !== null && !$this->repository->groupHasSubject($groupId, $subjectId)) {
            throw new DomainException('Subject not found in group.');
        }
        if ($user->role === 'teacher' && $subjectId === null && !$this->repository->teacherOwnsGroup($user->id, $groupId)) {
            throw new AttendanceReportForbiddenException('Forbidden.');
        }
        if ($user->role === 'teacher' && $subjectId !== null && !$this->repository->teacherOwnsGroupSubject($user->id, $groupId, $subjectId)) {
            throw new AttendanceReportForbiddenException('Forbidden.');
        }
        $dates = self::businessDates($date, $days);
        $students = $this->repository->students($user->schoolId, $groupId, $subjectId, $user->role === 'teacher' ? $user->id : null);
        $weekdays = [1 => 'Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
        $headers = ['Matrícula', 'Estudiante'];
        foreach ($dates as $businessDate) {
            $headers[] = $weekdays[(int) $businessDate->format('N')] . ' ' . $businessDate->format('d/m');
        }
        $rows = [];
        foreach ($students as $student) {
            $rows[] = array_merge([
                $student['enrollment_number'],
                $student['last_name'] . ', ' . $student['first_name'],
            ], array_fill(0, count($dates), ''));
        }
        $header = [
            'school' => $group['school_name'],
            'cycle' => $group['cycle_name'],
            'group' => $group['group_name'],
            'period' => $dates[0]->format('d/m/Y') . ' – ' . end($dates)->format('d/m/Y'),
            'logo_path' => $group['logo_path'],
            'filename' => 'attendance-' . $groupId . '-' . $startDate . '.pdf',
        ];
        if ($subject !== null) {
            $header['subject'] = $subject['name'];
            $header['teacher'] = trim(($subject['teacher_first_name'] ?? '') . ' ' . ($subject['teacher_last_name'] ?? ''));
        }
        $viewModel = new ReportViewModel('Lista de asistencia', $header, [[
            'headers' => $headers,
            'rows' => $rows,
        ]]);
        $rendered = $this->renderer->render($viewModel);
        return ['bytes' => $rendered->content, 'group_id' => $groupId, 'start_date' => $startDate];
    }

    public static function businessDates(DateTimeImmutable $date, int $days): array
    {
        $dates = [];
        while (count($dates) < $days) {
            if ((int) $date->format('N') < 6) {
                $dates[] = $date;
            }
            $date = $date->modify('+1 day');
        }
        return $dates;
    }
}
