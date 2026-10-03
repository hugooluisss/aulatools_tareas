<?php

declare(strict_types=1);

namespace App\Groups\Service;

use App\Auth\CurrentUser;
use App\Groups\Repository\GroupRepository;
use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use App\Shared\TransactionRunner;
use DomainException;
use App\Groups\Service\GroupException;

final class GroupService
{
    public function __construct(private GroupRepository $repository, private TransactionRunner $transactions, private TaskDeliveryEnrollmentHook $deliveryHook)
    {
    }

    public function list(CurrentUser $user, int $page, int $perPage): array
    {
        $result = $this->repository->list($user->schoolId, ($page - 1) * $perPage, $perPage);
        return [
            'data' => $result['data'],
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    public function view(CurrentUser $user, int $id): array
    {
        $group = $this->repository->find($user->schoolId, $id);
        if ($group === null) {
            throw new DomainException('Group not found.');
        }
        $group['subjects'] = $this->repository->subjects($user->schoolId, $id, 0, 1000)['data'];
        return $group;
    }

    public function create(CurrentUser $user, array $data): array
    {
        [$name, $cycleId, $subjectIds, $teacherId] = $this->validate($user, $data);
        $id = $this->transactions->run(function () use ($user, $name, $cycleId, $subjectIds, $teacherId): int {
            $id = $this->repository->create($user->schoolId, $cycleId, $name, $teacherId);
            $this->repository->replaceSubjects($id, $subjectIds);
            return $id;
        });
        return $this->view($user, $id);
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->view($user, $id);
        [$name, $cycleId, $subjectIds, $teacherId] = $this->validate($user, $data);
        $this->transactions->run(function () use ($id, $name, $cycleId, $subjectIds, $teacherId, $user): void {
            $this->repository->update($id, $cycleId, $name, $teacherId);
            $this->repository->replaceSubjects($id, $subjectIds, $cycleId, $this->deliveryHook);
        });
        return $this->view($user, $id);
    }

    public function delete(CurrentUser $user, int $id): void
    {
        $this->view($user, $id);
        $this->repository->delete($id);
    }

    public function subjects(CurrentUser $user, int $id, int $page, int $perPage): array
    {
        $this->view($user, $id);
        $result = $this->repository->subjects($user->schoolId, $id, ($page - 1) * $perPage, $perPage);
        return [
            'data' => $result['data'],
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    private function validate(CurrentUser $user, array $data): array
    {
        if (
            !isset($data['name'])
            || !is_string($data['name'])
            || trim($data['name']) === ''
            || !isset($data['cycle_id'])
            || filter_var($data['cycle_id'], FILTER_VALIDATE_INT) === false
            || (int) $data['cycle_id'] < 1
            || !isset($data['subject_ids'])
            || !is_array($data['subject_ids'])
            || (isset($data['teacher_id']) && (filter_var($data['teacher_id'], FILTER_VALIDATE_INT) === false || (int) $data['teacher_id'] < 1))
        ) {
            throw new \InvalidArgumentException('Invalid group data.');
        }
        $ids = [];
        foreach ($data['subject_ids'] as $subjectId) {
            if (filter_var($subjectId, FILTER_VALIDATE_INT) === false || (int) $subjectId < 1) {
                throw new \InvalidArgumentException('Invalid group data.');
            }
            $ids[] = (int) $subjectId;
        }
        $cycleId = (int) $data['cycle_id'];
        if (!$this->repository->cycleBelongsToSchool($user->schoolId, $cycleId)) {
            throw new \InvalidArgumentException('Invalid cycle_id.');
        }
        if (!$this->repository->subjectIdsActiveInSchool($user->schoolId, $ids)) {
            throw new GroupException('Every subject must be active and belong to the school.', 400);
        }
        $teacherId = isset($data['teacher_id']) ? (int) $data['teacher_id'] : null;
        if ($teacherId !== null && !$this->repository->teacherBelongsToSchool($user->schoolId, $teacherId)) {
            throw new DomainException('Teacher not found.');
        }
        return [trim($data['name']), $cycleId, array_values(array_unique($ids)), $teacherId];
    }
}
