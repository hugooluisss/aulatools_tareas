<?php

declare(strict_types=1);

namespace App\Enrollments\Service;

/**
 * Integration point for the tasks module to create pending deliveries for
 * existing non-cancelled tasks when a student is enrolled in a subject.
 */
interface TaskDeliveryEnrollmentHook
{
    public function onStudentEnrolled(int $studentId, int $subjectId): void;
}
