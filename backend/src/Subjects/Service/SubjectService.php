<?php

declare(strict_types=1);

namespace App\Subjects\Service;

use App\Auth\CurrentUser;
use App\Subjects\Repository\SubjectRepository;
use DomainException;

final class SubjectService
{
    public function __construct(private SubjectRepository $repository)
    {
    }

    public function list(CurrentUser $user, array $filters, int $page, int $perPage): array
    {
        $result = $this->repository->list(
            $user->schoolId,
            $user->role,
            $user->id,
            $filters,
            ($page - 1) * $perPage,
            $perPage,
        );
        return [
            'data' => $result['data'],
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    public function view(CurrentUser $user, int $id): array
    {
        $subject = $this->repository->find($user->schoolId, $id);
        if (
            $subject === null
            || ($user->role === 'teacher' && (int) $subject['teacher_id'] !== $user->id)
            || ($user->role === 'student' && !$this->repository->isEnrolled($id, $user->id))
        ) {
            throw new DomainException('Subject not found.');
        }
        return $subject;
    }

    public function create(CurrentUser $user, array $data): array
    {
        $this->validate($data, false);
        $this->assertAssignments($user, (int) $data['cycle_id'], (int) $data['teacher_id']);
        $id = $this->repository->create(
            (int) $data['cycle_id'],
            (int) $data['teacher_id'],
            trim($data['name']),
        );
        return $this->view($user, $id);
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->view($user, $id);
        $this->validate($data, true);
        $this->assertAssignments($user, (int) $data['cycle_id'], (int) $data['teacher_id']);
        if (!in_array($data['status'], ['in_progress', 'finished'], true)) {
            throw new DomainException('Invalid subject status.');
        }
        $this->repository->update(
            $id,
            (int) $data['cycle_id'],
            (int) $data['teacher_id'],
            trim($data['name']),
            $data['status'],
        );
        return $this->view($user, $id);
    }

    public function delete(CurrentUser $user, int $id): void
    {
        $this->view($user, $id);
        $this->repository->delete($id);
    }

    public function students(CurrentUser $user, int $id, int $page, int $perPage): array
    {
        $subject = $this->repository->find($user->schoolId, $id);
        if (
            $subject === null
            || ($user->role === 'teacher' && (int) $subject['teacher_id'] !== $user->id)
            || $user->role === 'student'
        ) {
            throw new DomainException('Subject not found.');
        }
        $result = $this->repository->students($id, ($page - 1) * $perPage, $perPage);
        return [
            'data' => $result['data'],
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    private function assertAssignments(CurrentUser $user, int $cycleId, int $teacherId): void
    {
        if (!$this->repository->cycleIsActive($user->schoolId, $cycleId)) {
            throw new DomainException('Cycle not found or not active.');
        }
        if (!$this->repository->teacherBelongsToSchool($user->schoolId, $teacherId)) {
            throw new DomainException('Teacher not found.');
        }
    }

    private function validate(array $data, bool $includeStatus): void
    {
        foreach (['cycle_id', 'teacher_id'] as $field) {
            if (
                !isset($data[$field])
                || filter_var($data[$field], FILTER_VALIDATE_INT) === false
                || (int) $data[$field] < 1
            ) {
                throw new \InvalidArgumentException('Invalid subject data.');
            }
        }
        if (!isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '') {
            throw new \InvalidArgumentException('Invalid subject data.');
        }
        if (
            $includeStatus
            && (!isset($data['status']) || !in_array($data['status'], ['in_progress', 'finished'], true))
        ) {
            throw new \InvalidArgumentException('Invalid subject data.');
        }
    }
}
