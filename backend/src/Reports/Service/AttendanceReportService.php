<?php

declare(strict_types=1);

namespace App\Reports\Service;

use App\Auth\CurrentUser;
use App\Reports\Repository\AttendanceReportRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use DomainException;
use DateTimeImmutable;
use DateTimeZone;

final class AttendanceReportService
{
    public function __construct(private AttendanceReportRepository $repository)
    {
    }

    public function generate(CurrentUser $user, array $query): array
    {
        $groupId = filter_var($query['group_id'] ?? null, FILTER_VALIDATE_INT);
        $days = filter_var($query['days'] ?? null, FILTER_VALIDATE_INT);
        $subjectId = isset($query['subject_id']) && $query['subject_id'] !== ''
            ? filter_var($query['subject_id'], FILTER_VALIDATE_INT)
            : null;
        $startDate = $query['start_date'] ?? null;
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

        $dates = self::businessDates($date, $days);
        $students = $this->repository->students($user->schoolId, $groupId, $subjectId);
        $html = $this->html($group, $subject, $dates, $students);
        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $pdf = new Dompdf($options);
        $pdf->setPaper('letter', 'portrait');
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();
        return ['bytes' => $pdf->output(), 'group_id' => $groupId, 'start_date' => $startDate];
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

    private function html(array $group, ?array $subject, array $dates, array $students): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logo = '';
        $logoPath = $group['logo_path'] ?? null;
        if (is_string($logoPath) && preg_match('#^/uploads/school/[a-zA-Z0-9._-]+$#D', $logoPath)) {
            $file = dirname(__DIR__, 3) . '/public' . $logoPath;
            $bytes = @file_get_contents($file);
            $mime = is_string($bytes) ? (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) : false;
            if (is_string($bytes) && is_string($mime) && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $logo = '<img class="logo" src="data:' . $mime . ';base64,' . base64_encode($bytes) . '">';
            }
        }
        $title = '<div class="heading">' . $logo . '<div><h1>Lista de asistencia</h1><p>' . $escape($group['school_name']) . ' · '
            . $escape($group['cycle_name']) . ' · ' . $escape($group['group_name']) . '</p>';
        if ($subject !== null) {
            $title .= '<p>' . $escape($subject['name']) . ' · '
                . $escape(trim(($subject['teacher_first_name'] ?? '') . ' ' . ($subject['teacher_last_name'] ?? ''))) . '</p>';
        }
        $title .= '<p>Periodo: ' . $escape($dates[0]->format('d/m/Y')) . ' – '
            . $escape(end($dates)->format('d/m/Y')) . '</p>';
        $title .= '</div></div>';
        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
            @page { margin: 20px; } body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            .heading { display: table; width: 100%; } .heading > * { display: table-cell; vertical-align: middle; }
            .logo { width: auto; max-width: 100px; max-height: 50px; margin-right: 12px; }
            h1 { font-size: 16px; margin: 0 0 6px; } p { margin: 3px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 12px; }
            thead { display: table-header-group; } th, td { border: 1px solid #555; padding: 3px; }
            th { background: #eee; }
            .enrollment { width: 55px; text-align: center; font-size: 8px; }
            .student { width: auto; } .date { width: 26px; text-align: center; font-size: 7px; }
            .check { height: 23px; }
            </style></head><body>' . $title . '<table><thead><tr><th class="enrollment">Matrícula</th><th class="student">Estudiante</th>';
        $weekdays = [1 => 'Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
        foreach ($dates as $date) {
            $html .= '<th class="date">' . $weekdays[(int) $date->format('N')] . '<br>' . $escape($date->format('d/m')) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($students as $student) {
            $html .= '<tr><td class="enrollment">' . $escape($student['enrollment_number']) . '</td><td class="student">'
                . $escape($student['last_name'] . ', ' . $student['first_name']) . '</td>'
                . str_repeat('<td class="check"></td>', count($dates)) . '</tr>';
        }
        return $html . '</tbody></table></body></html>';
    }
}
