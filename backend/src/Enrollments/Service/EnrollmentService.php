<?php

declare(strict_types=1);

namespace App\Enrollments\Service;

use App\Auth\CurrentUser;
use App\Enrollments\Repository\EnrollmentRepository;
use App\Shared\TransactionRunner;
use DomainException;

final class EnrollmentService
{
    public function __construct(
        private EnrollmentRepository $repository,
        private TransactionRunner $transactions,
        private TaskDeliveryEnrollmentHook $deliveryHook,
    ) {
    }

    public function enrollGroup(CurrentUser $user, int $groupId, int $studentId): array
    {
        $this->assertStudent($user, $studentId);
        if ($this->repository->group($user->schoolId, $groupId) === null) {
            throw new DomainException('Group not found.');
        }
        $subjects = $this->repository->groupSubjects($groupId, $user->schoolId);
        $this->transactions->run(function () use ($subjects, $studentId, $groupId): void {
            foreach ($subjects as $subject) {
                $subjectId = (int) $subject['id'];
                $this->repository->enroll($studentId, $subjectId, $groupId);
                $this->deliveryHook->onStudentEnrolled($studentId, $subjectId);
            }
        });
        return [
            'student_id' => $studentId,
            'group_id' => $groupId,
            'subject_ids' => array_map(static fn ($subject) => (int) $subject['id'], $subjects),
        ];
    }

    public function enrollSubject(CurrentUser $user, int $subjectId, int $studentId): array
    {
        $this->assertStudent($user, $studentId);
        if ($this->repository->subject($user->schoolId, $subjectId) === null) {
            throw new DomainException('Subject not found.');
        }
        $this->repository->enroll($studentId, $subjectId, null);
        $this->deliveryHook->onStudentEnrolled($studentId, $subjectId);
        return ['student_id' => $studentId, 'subject_id' => $subjectId];
    }

    public function unenrollSubject(CurrentUser $user, int $subjectId, int $studentId): void
    {
        $this->assertStudent($user, $studentId);
        if ($this->repository->subject($user->schoolId, $subjectId) === null) {
            throw new DomainException('Subject not found.');
        }
        $this->repository->unenroll($studentId, $subjectId);
    }

    private function assertStudent(CurrentUser $user, int $studentId): void
    {
        if (!$this->repository->studentBelongsToSchool($user->schoolId, $studentId)) {
            throw new DomainException('Student not found.');
        }
    }
}
