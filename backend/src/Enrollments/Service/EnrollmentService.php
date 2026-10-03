<?php

declare(strict_types=1);

namespace App\Enrollments\Service;

use App\Auth\CurrentUser;
use App\Enrollments\Repository\EnrollmentRepository;
use App\Shared\TransactionRunner;
use Yiisoft\Db\Exception\IntegrityException;

final class EnrollmentService
{
    public function __construct(
        private EnrollmentRepository $repository,
        private TransactionRunner $transactions,
        private TaskDeliveryEnrollmentHook $deliveryHook,
    ) {
    }

    public function list(CurrentUser $user, ?int $cycleId, ?int $studentId): array
    {
        $this->admin($user);
        if ($studentId !== null && !$this->repository->studentExists($user->schoolId, $studentId)) {
            throw new EnrollmentException('Student not found.', 404);
        }
        return $this->repository->list($user->schoolId, $cycleId, $studentId);
    }

    public function aspirants(CurrentUser $user): array
    {
        $this->admin($user);
        return $this->repository->aspirants($user->schoolId);
    }

    public function reenrollable(CurrentUser $user): array
    {
        $this->admin($user);
        return $this->repository->reenrollable($user->schoolId);
    }

    public function bulk(CurrentUser $user, string $type, array $studentIds, int $cycleId, int $groupId): array
    {
        $this->admin($user);
        if (!in_array($type, ['enrollment', 'reenrollment'], true) || $studentIds === []) {
            throw new EnrollmentException('Invalid type or student_ids.', 400);
        }
        $studentIds = array_values(array_unique($studentIds));
        $cycle = $this->repository->cycle($user->schoolId, $cycleId);
        if ($cycle === null) {
            throw new EnrollmentException('Cycle not found.', 404);
        }
        if ($cycle['status'] !== 'active') {
            throw new EnrollmentException('Cycle is finished.', 422);
        }
        $group = $this->repository->group($user->schoolId, $groupId);
        if ($group === null) {
            throw new EnrollmentException('Group not found.', 404);
        }
        if ((int) $group['cycle_id'] !== $cycleId) {
            throw new EnrollmentException('Group does not belong to cycle.', 400);
        }

        $found = array_map('intval', array_column($this->repository->studentsByIds($user->schoolId, $studentIds), 'student_id'));
        $missing = array_values(array_diff($studentIds, $found));
        if ($missing !== []) {
            throw new EnrollmentException('Students not found: ' . implode(', ', $missing), 404);
        }
        $eligible = array_map('intval', array_column($this->repository->eligibleIds($user->schoolId, $studentIds, $type), 'student_id'));
        $ineligible = array_values(array_diff($studentIds, $eligible));
        if ($ineligible !== []) {
            throw new EnrollmentException('Students are not eligible: ' . implode(', ', $ineligible), 422);
        }
        $destinationConflicts = array_map('intval', array_column($this->repository->destinationConflicts($studentIds, $cycleId), 'student_id'));
        if ($destinationConflicts !== []) {
            throw new EnrollmentException('Students already enrolled in destination cycle: ' . implode(', ', $destinationConflicts), 409);
        }
        if ($type === 'reenrollment') {
            $previousConflicts = array_map('intval', array_column($this->repository->previousCycleConflicts($studentIds, $cycleId), 'student_id'));
            if ($previousConflicts !== []) {
                throw new EnrollmentException('Destination cycle matches previous cycle for students: ' . implode(', ', $previousConflicts), 409);
            }
        }

        $ids = $this->transactions->run(function () use ($user, $studentIds, $cycleId, $groupId): array {
            $created = [];
            foreach ($studentIds as $studentId) {
                $created[] = $this->repository->create($studentId, $cycleId, $groupId);
            }
            $subjects = $this->repository->syncGroupSubjectsForStudents($user->schoolId, $groupId, $studentIds);
            if ($subjects !== []) {
                $this->deliveryHook->onGroupSubjectsAdded($groupId, $cycleId, $subjects);
            }
            return $created;
        });
        return $this->repository->findMany($user->schoolId, $ids);
    }

    public function create(CurrentUser $user, int $studentId, int $cycleId, int $groupId): array
    {
        $this->admin($user);
        $this->student($user, $studentId);
        $cycle = $this->repository->cycle($user->schoolId, $cycleId);
        if ($cycle === null) {
            throw new EnrollmentException('Cycle not found.', 404);
        }
        if ($cycle['status'] !== 'active') {
            throw new EnrollmentException('Cycle is finished.', 422);
        }
        $group = $this->repository->group($user->schoolId, $groupId);
        if ($group === null) {
            throw new EnrollmentException('Group not found.', 404);
        }
        if ((int) $group['cycle_id'] !== $cycleId) {
            throw new EnrollmentException('Group does not belong to cycle.', 400);
        }
        if ($this->repository->existsForCycle($studentId, $cycleId)) {
            throw new EnrollmentException('Student already enrolled in cycle.', 409);
        }
        try {
            $id = $this->transactions->run(function () use ($user, $studentId, $cycleId, $groupId): int {
                $id = $this->repository->create($studentId, $cycleId, $groupId);
                $this->enrollCycleGroup($user->schoolId, $studentId, $groupId);
                return $id;
            });
        } catch (IntegrityException $exception) {
            if ($this->repository->existsForCycle($studentId, $cycleId)) {
                throw new EnrollmentException('Student already enrolled in cycle.', 409);
            }
            throw $exception;
        }
        return $this->repository->find($user->schoolId, $id);
    }

    public function update(CurrentUser $user, int $id, int $groupId): array
    {
        $this->admin($user);
        $inscription = $this->repository->find($user->schoolId, $id)
            ?? throw new EnrollmentException('Enrollment not found.', 404);
        $group = $this->repository->group($user->schoolId, $groupId);
        if ($group === null) {
            throw new EnrollmentException('Group not found.', 404);
        }
        if ((int) $group['cycle_id'] !== (int) $inscription['cycle_id']) {
            throw new EnrollmentException('Group does not belong to cycle.', 400);
        }
        if ((int) $groupId === (int) $inscription['group_id']) {
            return $inscription;
        }
        $this->transactions->run(function () use ($user, $inscription, $id, $groupId): void {
            $this->repository->removeGroupBindings($id, (int) $inscription['group_id']);
            $this->repository->updateGroup($id, $groupId);
            $this->enrollCycleGroup($user->schoolId, (int) $inscription['student_id'], $groupId);
        });
        return $this->repository->find($user->schoolId, $id);
    }

    public function delete(CurrentUser $user, int $id): void
    {
        $this->admin($user);
        $this->repository->find($user->schoolId, $id)
            ?? throw new EnrollmentException('Enrollment not found.', 404);
        $this->transactions->run(function () use ($id): void {
            $this->repository->delete($id);
        });
    }

    public function enrollCycleGroup(int $schoolId, int $studentId, int $groupId): array
    {
        $subjectIds = $this->repository->syncGroupSubjects($schoolId, $studentId, $groupId);
        $group = $this->repository->group($schoolId, $groupId);
        if ($subjectIds !== [] && $group !== null) {
            $this->deliveryHook->onGroupSubjectsAdded($groupId, (int) $group['cycle_id'], $subjectIds);
        }
        return $subjectIds;
    }

    private function student(CurrentUser $user, int $studentId): void
    {
        if (!$this->repository->studentExists($user->schoolId, $studentId)) {
            throw new EnrollmentException('Student not found.', 404);
        }
    }

    private function admin(CurrentUser $user): void
    {
        if ($user->role !== 'admin') {
            throw new EnrollmentException('Forbidden.', 403);
        }
    }
}
