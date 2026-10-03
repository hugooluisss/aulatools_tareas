<?php

declare(strict_types=1);

namespace App\Tasks\Service;

use App\Tasks\Repository\TaskRepository;

final class TaskDeliveryService
{
    public function __construct(private TaskRepository $repository)
    {
    }

    public function createForEnrollment(int $studentId, int $subjectId, int $cycleId): void
    {
        $this->repository->createForEnrollment($studentId, $subjectId, $cycleId);
    }

    public function createForGroupSubjects(int $groupId, int $cycleId, array $subjectIds): void
    {
        $this->repository->createForGroupSubjects($groupId, $cycleId, $subjectIds);
    }
}
