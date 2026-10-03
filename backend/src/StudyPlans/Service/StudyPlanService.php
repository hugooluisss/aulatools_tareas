<?php

declare(strict_types=1);

namespace App\StudyPlans\Service;

use App\Auth\CurrentUser;
use App\StudyPlans\Repository\StudyPlanRepository;
use DomainException;
use Yiisoft\Db\Exception\IntegrityException;

final class StudyPlanService
{
    public function __construct(private StudyPlanRepository $repository)
    {
    }

    public function list(CurrentUser $user): array
    {
        return $this->repository->list($user->schoolId);
    }

    public function view(CurrentUser $user, int $id): array
    {
        return $this->repository->find($user->schoolId, $id) ?? throw new DomainException('Study plan not found.');
    }

    public function create(CurrentUser $user, array $data): array
    {
        [$code, $name] = $this->validate($data, false);
        try {
            return $this->view($user, $this->repository->create($user->schoolId, $code, $name));
        } catch (IntegrityException $exception) {
            if ($this->repository->codeExists($user->schoolId, $code)) {
                throw new StudyPlanException('Study plan code already exists.', 409);
            }
            throw $exception;
        }
    }

    public function update(CurrentUser $user, int $id, array $data): array
    {
        $this->view($user, $id);
        [$code, $name] = $this->validate($data, true);
        try {
            $this->repository->update($user->schoolId, $id, $code, $name, $data['status']);
        } catch (IntegrityException $exception) {
            if ($this->repository->codeExists($user->schoolId, $code, $id)) {
                throw new StudyPlanException('Study plan code already exists.', 409);
            }
            throw $exception;
        }
        return $this->view($user, $id);
    }

    public function delete(CurrentUser $user, int $id): void
    {
        $plan = $this->view($user, $id);
        if ((int) $plan['subjects_count'] > 0) {
            throw new StudyPlanException('Study plan has subjects and cannot be deleted.', 409);
        }
        $this->repository->delete($user->schoolId, $id);
    }

    private function validate(array $data, bool $status): array
    {
        foreach (['code', 'name'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '' || strlen(trim($data[$field])) > ($field === 'code' ? 40 : 150)) {
                throw new \InvalidArgumentException('Invalid study plan data.');
            }
        }
        if ($status && (!isset($data['status']) || !in_array($data['status'], ['active', 'inactive'], true))) {
            throw new \InvalidArgumentException('Invalid study plan data.');
        }
        return [trim($data['code']), trim($data['name'])];
    }
}
