<?php

declare(strict_types=1);

namespace App\Reports\Service;

use App\Auth\CurrentUser;
use App\Reports\Model\ReportViewModel;
use App\Reports\Renderer\ReportRenderer;
use App\Reports\Repository\ReportRepository;
use DomainException;

final class TaskReportCardService
{
    public function __construct(private ReportRepository $repository, private ReportRenderer $renderer)
    {
    }

    public function generate(CurrentUser $user, array $data): array
    {
        if (!in_array($user->role, ['admin', 'teacher'], true)) {
            throw new DomainException('Forbidden.');
        }
        $subjectId = $this->positiveInteger($data['subject_id'] ?? null);
        $studentIds = $data['student_ids'] ?? null;
        if ($subjectId === null || !is_array($studentIds) || $studentIds === []) {
            throw new \InvalidArgumentException('Invalid task report card parameters.');
        }
        $validatedIds = [];
        foreach ($studentIds as $studentId) {
            $id = $this->positiveInteger($studentId);
            if ($id === null || in_array($id, $validatedIds, true)) {
                throw new \InvalidArgumentException('Invalid task report card parameters.');
            }
            $validatedIds[] = $id;
        }
        $subject = $this->repository->taskSubject($user->schoolId, $subjectId);
        if ($subject === null) {
            throw new DomainException('Subject not found.');
        }
        if ($user->role === 'teacher' && (int) $subject['teacher_id'] !== $user->id) {
            throw new AttendanceReportForbiddenException('Forbidden.');
        }
        $students = $this->repository->enrolledStudents($user->schoolId, $subjectId, $validatedIds);
        if (count($students) !== count($validatedIds)) {
            throw new \InvalidArgumentException('All students must be enrolled in the subject.');
        }
        $studentIds = array_map(static fn (array $student): int => (int) $student['id'], $students);
        $tasks = $this->repository->tasks($subjectId, $studentIds);
        $sections = [];
        foreach ($students as $student) {
            $rows = [];
            foreach ($tasks as $task) {
                if ((int) $task['cycle_id'] !== (int) $student['cycle_id']) {
                    continue;
                }
                if ($task['student_id'] !== null && (int) $task['student_id'] !== (int) $student['id']) {
                    continue;
                }
                $rows[] = [
                    $task['task_name'],
                    $task['due_date'] ?? '—',
                    $task['delivered_date'] ?? '—',
                    $task['grade'] === null ? '—' : (string) $task['grade'],
                ];
            }
            $sections[] = [
                'header' => [
                    'Escuela' => $subject['school_name'],
                    'Ciclo' => $student['cycle_name'],
                    'Asignatura' => $subject['subject_name'],
                    'Docente' => trim($subject['teacher_first_name'] . ' ' . $subject['teacher_last_name']),
                    'Estudiante' => trim($student['first_name'] . ' ' . $student['last_name']),
                    'Matrícula' => $student['enrollment_number'],
                ],
                'table' => [
                    'headers' => ['Tarea', 'Fecha de entrega', 'Fecha entregada', 'Calificación'],
                    'rows' => $rows,
                ],
            ];
        }
        $filename = 'boleta-tareas-' . preg_replace('/[^a-zA-Z0-9._-]+/', '-', $subject['subject_name']) . '.pdf';
        $viewModel = new ReportViewModel('Boleta de tareas', [
            'filename' => $filename,
            'subject' => $subject['subject_name'],
        ], [], $sections);
        $rendered = $this->renderer->render($viewModel);
        return ['bytes' => $rendered->content, 'filename' => $rendered->filename, 'content_type' => $rendered->contentType];
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (!is_int($value) && !is_string($value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id !== false && $id > 0 ? $id : null;
    }
}
