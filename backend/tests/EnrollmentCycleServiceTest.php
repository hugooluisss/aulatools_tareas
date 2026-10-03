<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Enrollments\Repository\EnrollmentRepository;
use App\Enrollments\Service\EnrollmentException;
use App\Enrollments\Service\EnrollmentService;
use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use App\Shared\TransactionRunner;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class EnrollmentCycleServiceTest extends TestCase
{
    public function testChangeGroupRemovesOldGroupSubjectsAndAddsNewGroupSubjects(): void
    {
        $repository = new CycleEnrollmentRepository();
        $service = $this->service($repository);

        $result = $service->update(new CurrentUser(1, 'admin', 13), 900, 12);

        self::assertSame(12, $result['group_id']);
        self::assertSame([[900, 11]], $repository->removedGroups);
        self::assertSame([[57, 12]], $repository->syncedGroups);
    }

    public function testRemovingEnrollmentDeletesEnrollmentAndReliesOnBindingCascade(): void
    {
        $repository = new CycleEnrollmentRepository();
        $service = $this->service($repository);

        $service->delete(new CurrentUser(1, 'admin', 13), 900);

        self::assertSame([900], $repository->deleted);
        self::assertSame([], $repository->removedGroups);
    }

    public function testListIsAdminOnly(): void
    {
        $this->expectException(EnrollmentException::class);
        $this->service(new CycleEnrollmentRepository())->list(new CurrentUser(2, 'teacher', 13), null, null);
    }

    private function service(CycleEnrollmentRepository $repository): EnrollmentService
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        return new EnrollmentService($repository, new TransactionRunner($db), new CycleDeliveryHook());
    }
}

final class CycleEnrollmentRepository extends EnrollmentRepository
{
    public array $removedGroups = [];
    public array $syncedGroups = [];
    public array $deleted = [];
    private array $row = ['id' => 900, 'student_id' => 57, 'cycle_id' => 14, 'group_id' => 11];

    public function __construct()
    {
    }

    public function find(int $schoolId, int $id): ?array
    {
        return $this->row;
    }

    public function group(int $schoolId, int $groupId): ?array
    {
        return ['id' => $groupId, 'cycle_id' => 14];
    }

    public function removeGroupBindings(int $enrollmentId, int $groupId): void
    {
        $this->removedGroups[] = [$enrollmentId, $groupId];
    }

    public function updateGroup(int $id, int $groupId): void
    {
        $this->row['group_id'] = $groupId;
    }

    public function syncGroupSubjects(int $schoolId, int $studentId, int $groupId): array
    {
        $this->syncedGroups[] = [$studentId, $groupId];
        return [201, 202];
    }

    public function delete(int $id): void
    {
        $this->deleted[] = $id;
    }
}

final class CycleDeliveryHook implements TaskDeliveryEnrollmentHook
{
    public function onStudentEnrolled(int $studentId, int $subjectId, int $cycleId): void
    {
    }

    public function onGroupSubjectsAdded(int $groupId, int $cycleId, array $subjectIds): void
    {
    }
}
