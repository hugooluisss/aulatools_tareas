<?php

declare(strict_types=1);

namespace App\Subjects\Service;

use App\Auth\CurrentUser;
use App\Subjects\Repository\SubjectRepository;
use DomainException;
use Yiisoft\Db\Exception\IntegrityException;

final class SubjectService
{
    public function __construct(private SubjectRepository $repository)
    {
    }

    public function list(CurrentUser $user, array $filters, int $page, int $perPage): array
    {
        $result = $this->repository->list($user->schoolId, $user->role, $user->id, $filters, ($page - 1) * $perPage, $perPage);
        return $result['data'];
    }

    public function view(CurrentUser $user, int $id): array
    {
        $subject = $this->repository->find($user->schoolId, $id);
        if ($subject === null || ($user->role === 'teacher' && (int) $subject['teacher_id'] !== $user->id)
            || ($user->role === 'student' && !$this->repository->isEnrolled($id, $user->id))) {
            throw new DomainException('Subject not found.');
        }
        return $subject;
    }

    public function create(CurrentUser $user, array $data): array
    {
        $data = $this->normalizePlanId($data);
        $this->validate($data, false);
        $this->assertAssignments($user, (int) $data['teacher_id'], $data['plan_id'] ?? null);
        try {
            $id = $this->repository->create($user->schoolId, trim($data['code']), (int) $data['teacher_id'], trim($data['name']), isset($data['plan_id']) ? (int) $data['plan_id'] : null);
        } catch (IntegrityException $exception) {
            if ($this->repository->codeExists($user->schoolId, trim($data['code']))) {
                throw new SubjectException('Subject code already exists.', 409);
            }
            throw $exception;
        }
        return $this->view($user, $id);
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->view($user, $id);
        $data = $this->normalizePlanId($data);
        $this->validate($data, true);
        $this->assertAssignments($user, (int) $data['teacher_id'], $data['plan_id'] ?? null);
        try {
            $this->repository->update($user->schoolId, $id, trim($data['code']), (int) $data['teacher_id'], trim($data['name']), $data['status'], isset($data['plan_id']) ? (int) $data['plan_id'] : null);
        } catch (IntegrityException $exception) {
            if ($this->repository->codeExists($user->schoolId, trim($data['code']), $id)) {
                throw new SubjectException('Subject code already exists.', 409);
            }
            throw $exception;
        }
        return $this->view($user, $id);
    }

    public function delete(CurrentUser $user, int $id): void
    {
        $this->view($user, $id);
        if ($this->repository->studentCount($id) > 0) {
            throw new SubjectException('Subject has enrolled students and cannot be deleted.', 409);
        }
        $this->repository->delete($id);
    }

    public function students(CurrentUser $user, int $id, int $page, int $perPage, ?int $cycleId = null): array
    {
        $subject = $this->repository->find($user->schoolId, $id);
        if ($subject === null || ($user->role === 'teacher' && (int) $subject['teacher_id'] !== $user->id) || $user->role === 'student') {
            throw new DomainException('Subject not found.');
        }
        $result = $this->repository->students($id, ($page - 1) * $perPage, $perPage, $cycleId);
        return $result;
    }

    private function assertAssignments(CurrentUser $user, int $teacherId, mixed $planId): void
    {
        if (!$this->repository->teacherBelongsToSchool($user->schoolId, $teacherId)) {
            throw new DomainException('Teacher not found.');
        }
        if ($planId !== null && !$this->repository->activePlanBelongsToSchool($user->schoolId, (int) $planId)) {
            throw new SubjectException('Study plan not found or inactive.', 422);
        }
    }

    private function validate(array $data, bool $includeStatus): void
    {
        foreach (['teacher_id'] as $field) {
            if (!isset($data[$field]) || filter_var($data[$field], FILTER_VALIDATE_INT) === false || (int) $data[$field] < 1) {
                throw new \InvalidArgumentException('Invalid subject data.');
            }
        }
        foreach (['code', 'name'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '' || strlen(trim($data[$field])) > ($field === 'code' ? 40 : 150)) {
                throw new \InvalidArgumentException('Invalid subject data.');
            }
        }
        if (isset($data['plan_id']) && $data['plan_id'] !== '' && (filter_var($data['plan_id'], FILTER_VALIDATE_INT) === false || (int) $data['plan_id'] < 1)) {
            throw new \InvalidArgumentException('Invalid subject data.');
        }
        if ($includeStatus && (!isset($data['status']) || !in_array($data['status'], ['active', 'inactive'], true))) {
            throw new \InvalidArgumentException('Invalid subject data.');
        }
    }

    private function normalizePlanId(array $data): array
    {
        if (($data['plan_id'] ?? null) === '') {
            $data['plan_id'] = null;
        }
        return $data;
    }
}
