<?php

declare(strict_types=1);

namespace App\Tasks\Service;

use App\Tasks\Repository\TaskRepository;

final class TaskDeliveryService
{
    public function __construct(private TaskRepository $repository)
    {
    }

    public function createForEnrollment(int $studentId, int $subjectId): void
    {
        $this->repository->createForEnrollment($studentId, $subjectId);
    }
}
