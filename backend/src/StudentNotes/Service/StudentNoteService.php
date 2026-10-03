<?php

declare(strict_types=1);

namespace App\StudentNotes\Service;

use App\Auth\CurrentUser;
use App\StudentNotes\Repository\StudentNoteRepository;

final class StudentNoteService
{
    public function __construct(private StudentNoteRepository $repository)
    {
    }

    public function list(CurrentUser $user, int $studentId, int $page, int $perPage): array
    {
        $this->access($user, $studentId);
        $this->pagination($page, $perPage);
        $result = $this->repository->list($studentId, ($page - 1) * $perPage, $perPage);
        return [
            'data' => array_map([$this, 'format'], $result['data']),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    public function create(CurrentUser $user, int $studentId, array $data): array
    {
        $this->access($user, $studentId);
        $body = $data['body'] ?? null;
        if (!is_string($body) || trim($body) === '' || mb_strlen(trim($body), 'UTF-8') > 2000) {
            throw new StudentNoteException('Note must contain 1 to 2000 characters.', 400);
        }
        return $this->format($this->repository->find(
            $this->repository->create($studentId, $user->id, trim($body)),
        ));
    }

    private function access(CurrentUser $user, int $studentId): void
    {
        if ($user->role === 'student') {
            throw new StudentNoteException('Student not found.', 404);
        }
        if ($this->repository->student($user->schoolId, $studentId) === null) {
            throw new StudentNoteException('Student not found.', 404);
        }
        if ($user->role === 'admin') {
            return;
        }
        if ($user->role === 'teacher' && $this->repository->teacherHasStudent($user->id, $studentId)) {
            return;
        }
        throw new StudentNoteException('Forbidden.', 403);
    }

    private function pagination(int $page, int $perPage): void
    {
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new StudentNoteException('Invalid pagination.', 400);
        }
    }

    private function format(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'student_id' => (int) $row['student_id'],
            'author' => [
                'id' => (int) $row['author_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'role' => $row['role'],
            ],
            'body' => $row['body'],
            'created_at' => (new \DateTimeImmutable($row['created_at'], new \DateTimeZone('UTC')))->format(DATE_ATOM),
        ];
    }
}
