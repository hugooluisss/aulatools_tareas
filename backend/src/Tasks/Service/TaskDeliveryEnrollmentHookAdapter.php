<?php

declare(strict_types=1);

namespace App\Tasks\Service;

use App\Enrollments\Service\TaskDeliveryEnrollmentHook;

final class TaskDeliveryEnrollmentHookAdapter implements TaskDeliveryEnrollmentHook
{
    public function __construct(private TaskDeliveryService $deliveries)
    {
    }

    public function onStudentEnrolled(int $studentId, int $subjectId): void
    {
        $this->deliveries->createForEnrollment($studentId, $subjectId);
    }
}
