<?php

declare(strict_types=1);

namespace App\Enrollments\Service;

use App\Auth\CurrentUser;
use App\Enrollments\Repository\SubjectBindingRepository;
use App\Shared\TransactionRunner;
use DomainException;

class SubjectBindingService
{
    public function __construct(
        private SubjectBindingRepository $repository,
        private TransactionRunner $transactions,
        private TaskDeliveryEnrollmentHook $deliveryHook,
    ) {
    }

    public function enrollSubject(CurrentUser $user, int $subjectId, int $studentId, int $cycleId): array
    {
        $this->assertStudent($user, $studentId);
        if ($this->repository->subject($user->schoolId, $subjectId) === null) {
            throw new DomainException('Subject not found.');
        }
        $cycle = $this->repository->cycle($user->schoolId, $cycleId);
        if ($cycle === null) {
            throw new EnrollmentException('Cycle not found.', 404);
        }
        if ($cycle['status'] !== 'active') {
            throw new EnrollmentException('Cycle is finished.', 422);
        }
        if (!$this->repository->bindStudent($subjectId, $cycleId, $studentId)) {
            throw new EnrollmentException('Student already enrolled in subject.', 409);
        }
        $this->deliveryHook->onStudentEnrolled($studentId, $subjectId, $cycleId);
        return ['student_id' => $studentId, 'subject_id' => $subjectId, 'cycle_id' => $cycleId];
    }

    public function enrollSubjectsBulk(CurrentUser $user, int $subjectId, int $cycleId, array $studentIds): array
    {
        $this->assertAdmin($user);
        if ($studentIds === []) {
            throw new \InvalidArgumentException('Invalid student_ids.');
        }
        $studentIds = array_values(array_unique($studentIds));
        $subject = $this->repository->subject($user->schoolId, $subjectId);
        if ($subject === null) {
            throw new EnrollmentException('Subject not found.', 404);
        }
        $cycle = $this->repository->cycle($user->schoolId, $cycleId);
        if ($cycle === null) {
            throw new EnrollmentException('Cycle not found.', 404);
        }
        if ($cycle['status'] !== 'active') {
            throw new EnrollmentException('Cycle is finished.', 422);
        }

        $found = array_map('intval', array_column(
            $this->repository->studentsByIds($user->schoolId, $studentIds), 'student_id',
        ));
        $missing = array_values(array_diff($studentIds, $found));
        if ($missing !== []) {
            throw new EnrollmentException('Students not found: ' . implode(', ', $missing), 404);
        }
        $inscribed = array_map('intval', array_column(
            $this->repository->studentsWithCycleInscription($user->schoolId, $cycleId, $studentIds),
            'student_id',
        ));
        $withoutInscription = array_values(array_diff($studentIds, $inscribed));
        if ($withoutInscription !== []) {
            throw new EnrollmentException('Students not enrolled in subject cycle: ' . implode(', ', $withoutInscription), 400);
        }
        $enrolled = array_map('intval', array_column($this->repository->enrolledStudents($subjectId, $cycleId, $studentIds), 'student_id'));
        $duplicates = array_values(array_intersect($studentIds, $enrolled));
        if ($duplicates !== []) {
            throw new EnrollmentException('Students already enrolled in subject: ' . implode(', ', $duplicates), 409);
        }

        $this->transactions->run(function () use ($subjectId, $cycleId, $studentIds): void {
            $this->repository->bindStudents($subjectId, $cycleId, $studentIds);
            foreach ($studentIds as $studentId) {
                $this->deliveryHook->onStudentEnrolled($studentId, $subjectId, $cycleId);
            }
        });
        return array_map(static fn (int $studentId): array => [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'cycle_id' => $cycleId,
        ], $studentIds);
    }

    public function unenrollSubject(CurrentUser $user, int $subjectId, int $studentId, int $cycleId): void
    {
        $this->assertStudent($user, $studentId);
        if ($this->repository->subject($user->schoolId, $subjectId) === null) {
            throw new DomainException('Subject not found.');
        }
        $this->repository->unbindStudent($subjectId, $cycleId, $studentId);
    }

    public function reassignTeachers(CurrentUser $user, int $subjectId, int $cycleId, array $studentIds, int $teacherId): array
    {
        $this->assertAdmin($user);
        if ($studentIds === []) {
            throw new \InvalidArgumentException('Invalid student_ids.');
        }
        $studentIds = array_values(array_unique($studentIds));
        if ($this->repository->subject($user->schoolId, $subjectId) === null) {
            throw new EnrollmentException('Subject not found.', 404);
        }
        $cycle = $this->repository->cycle($user->schoolId, $cycleId);
        if ($cycle === null) {
            throw new EnrollmentException('Cycle not found.', 404);
        }
        if ($cycle['status'] !== 'active') {
            throw new EnrollmentException('Cycle is finished.', 422);
        }
        if (!$this->repository->teacherBelongsToSchool($user->schoolId, $teacherId)) {
            throw new EnrollmentException('Teacher not found.', 404);
        }
        $found = array_map('intval', array_column($this->repository->bindingStudents($subjectId, $cycleId, $studentIds), 'student_id'));
        $missing = array_values(array_diff($studentIds, $found));
        if ($missing !== []) {
            throw new EnrollmentException('Students do not have a subject binding: ' . implode(', ', $missing), 400);
        }
        $this->transactions->run(fn () => $this->repository->reassignTeachers($subjectId, $cycleId, $studentIds, $teacherId));
        return ['updated' => count($studentIds)];
    }

    private function assertStudent(CurrentUser $user, int $studentId): void
    {
        if (!$this->repository->studentBelongsToSchool($user->schoolId, $studentId)) {
            throw new DomainException('Student not found.');
        }
    }

    private function assertAdmin(CurrentUser $user): void
    {
        if ($user->role !== 'admin') {
            throw new EnrollmentException('Forbidden.', 403);
        }
    }
}
