<?php

declare(strict_types=1);

namespace App\Cycles\Service;

use App\Auth\CurrentUser;
use App\Cycles\Repository\CycleRepository;
use App\Shared\TransactionRunner;

final class CycleService
{
    public function __construct(private CycleRepository $repository, private TransactionRunner $transactions)
    {
    }

    public function list(CurrentUser $user, int $page, int $perPage): array
    {
        $this->pagination($page, $perPage);
        $result = $this->repository->list($user->schoolId, ($page - 1) * $perPage, $perPage);
        return \App\Shared\Paginator::build($result['data'], $result['total'], $page, $perPage);
    }

    public function find(CurrentUser $user, int $id): array
    {
        $this->admin($user);
        return $this->repository->find($user->schoolId, $id) ?? throw new CycleException('Cycle not found.', 404);
    }

    public function create(CurrentUser $user, array $data): array
    {
        $this->admin($user);
        $this->validate($data);
        return $this->find($user, $this->repository->create($user->schoolId, $data));
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->admin($user);
        $this->find($user, $id);
        $this->validate($data);
        $this->repository->update($user->schoolId, $id, $data);
        return $this->find($user, $id);
    }

    public function finish(CurrentUser $user, int $id): array
    {
        $this->admin($user);
        $cycle = $this->find($user, $id);
        if ($cycle['status'] === 'finished') {
            throw new CycleException('Cycle is already finished.', 422);
        }
        $this->transactions->run(function () use ($user, $id): void {
            $this->repository->finish($user->schoolId, $id);
        });
        return $this->find($user, $id);
    }

    private function validate(array $data): void
    {
        foreach (['name', 'starts_on', 'ends_on'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new CycleException("Invalid {$field}.", 400);
            }
        }
        foreach (['starts_on', 'ends_on'] as $field) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data[$field]);
            if ($date === false || $date->format('Y-m-d') !== $data[$field]) {
                throw new CycleException("Invalid {$field}.", 400);
            }
        }
        if ($data['ends_on'] < $data['starts_on']) {
            throw new CycleException('End date must not precede start date.', 400);
        }
    }

    private function pagination(int $page, int $perPage): void
    {
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new CycleException('Invalid pagination.', 400);
        }
    }

    private function admin(CurrentUser $user): void
    {
        if ($user->role !== 'admin') {
            throw new CycleException('Forbidden.', 403);
        }
    }
}
