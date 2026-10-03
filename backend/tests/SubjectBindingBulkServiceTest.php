<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Enrollments\Repository\SubjectBindingRepository;
use App\Enrollments\Service\EnrollmentException;
use App\Enrollments\Service\SubjectBindingService;
use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use App\Shared\TransactionRunner;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class SubjectBindingBulkServiceTest extends TestCase
{
    public function testBulkAddsBindingsInOneTransactionAndCreatesDeliveries(): void
    {
        $repository = new BindingRepositoryFake();
        $hook = new BindingDeliveryHookFake();
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->expects(self::once())->method('beginTransaction')->willReturn($transaction);
        $service = new SubjectBindingService($repository, new TransactionRunner($db), $hook);

        $result = $service->enrollSubjectsBulk(new CurrentUser(1, 'admin', 13), 31, 14, [57, 58]);

        self::assertSame([
            ['student_id' => 57, 'subject_id' => 31, 'cycle_id' => 14],
            ['student_id' => 58, 'subject_id' => 31, 'cycle_id' => 14],
        ], $result);
        self::assertSame([[31, 14, [57, 58]]], $repository->bound);
        self::assertSame([[57, 31, 14], [58, 31, 14]], $hook->calls);
    }

    public function testRejectsStudentsWithoutCycleEnrollmentBeforeWriting(): void
    {
        $repository = new BindingRepositoryFake();
        $repository->inscribed = [57];
        $service = new SubjectBindingService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new BindingDeliveryHookFake(),
        );

        try {
            $service->enrollSubjectsBulk(new CurrentUser(1, 'admin', 13), 31, 14, [57, 58]);
            self::fail('Expected enrollment exception.');
        } catch (EnrollmentException $exception) {
            self::assertSame(400, $exception->status);
            self::assertStringContainsString('58', $exception->getMessage());
        }
        self::assertSame([], $repository->bound);
    }
}

final class BindingRepositoryFake extends SubjectBindingRepository
{
    public array $inscribed = [57, 58];
    public array $bound = [];

    public function __construct()
    {
    }

    public function subject(int $schoolId, int $subjectId): ?array { return ['id' => $subjectId]; }
    public function cycle(int $schoolId, int $cycleId): ?array { return ['id' => $cycleId, 'status' => 'active']; }
    public function studentsByIds(int $schoolId, array $studentIds): array { return array_map(static fn ($id) => ['student_id' => $id], $studentIds); }
    public function studentsWithCycleInscription(int $schoolId, int $cycleId, array $studentIds): array { return array_map(static fn ($id) => ['student_id' => $id], $this->inscribed); }
    public function enrolledStudents(int $subjectId, int $cycleId, array $studentIds): array { return []; }
    public function bindStudents(int $subjectId, int $cycleId, array $studentIds): void { $this->bound[] = [$subjectId, $cycleId, $studentIds]; }
}

final class BindingDeliveryHookFake implements TaskDeliveryEnrollmentHook
{
    public array $calls = [];
    public function onStudentEnrolled(int $studentId, int $subjectId, int $cycleId): void { $this->calls[] = [$studentId, $subjectId, $cycleId]; }
    public function onGroupSubjectsAdded(int $groupId, int $cycleId, array $subjectIds): void { }
}
