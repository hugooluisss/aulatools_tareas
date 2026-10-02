<?php

declare(strict_types=1);

namespace App\Groups\Service;

use App\Auth\CurrentUser;
use App\Groups\Repository\GroupRepository;
use App\Shared\TransactionRunner;
use DomainException;

final class GroupService
{
    public function __construct(private GroupRepository $repository, private TransactionRunner $transactions)
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
        [$name, $subjectIds] = $this->validate($user, $data);
        $id = $this->transactions->run(function () use ($user, $name, $subjectIds): int {
            $id = $this->repository->create($user->schoolId, $name);
            $this->repository->replaceSubjects($id, $subjectIds);
            return $id;
        });
        return $this->view($user, $id);
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->view($user, $id);
        [$name, $subjectIds] = $this->validate($user, $data);
        $this->transactions->run(function () use ($id, $name, $subjectIds): void {
            $this->repository->update($id, $name);
            $this->repository->replaceSubjects($id, $subjectIds);
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
            || !isset($data['subject_ids'])
            || !is_array($data['subject_ids'])
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
        if (!$this->repository->subjectIdsBelongToSchool($user->schoolId, $ids)) {
            throw new DomainException('Subject not found.');
        }
        return [trim($data['name']), array_values(array_unique($ids))];
    }
}
