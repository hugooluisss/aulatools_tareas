<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Enrollments\Repository\EnrollmentRepository;
use App\Enrollments\Service\EnrollmentService;
use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use App\Shared\TransactionRunner;
use App\Tasks\Repository\TaskRepository;
use App\Tasks\Service\TaskDeliveryEnrollmentHookAdapter;
use App\Tasks\Service\TaskDeliveryService;
use DomainException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class EnrollmentServiceTest extends TestCase
{
    public function testGroupEnrollmentIsIdempotentAndCallsDeliveryHookForEachGroupSubject(): void
    {
        $repository = new FakeEnrollmentRepository();
        $hook = new FakeDeliveryHook();
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        $service = new EnrollmentService(
            $repository,
            new TransactionRunner($db),
            $hook,
        );
        $user = new CurrentUser(1, 'admin', 2);
        self::assertSame(
            ['student_id' => 9, 'group_id' => 3, 'subject_ids' => [21, 22]],
            $service->enrollGroup($user, 3, 9),
        );
        self::assertSame([[9, 21, 3], [9, 22, 3]], $repository->enrollments);
        self::assertSame([[9, 21], [9, 22]], $hook->calls);
    }

    public function testCrossSchoolGroupIsHidden(): void
    {
        $service = new EnrollmentService(
            new FakeEnrollmentRepository(false),
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new FakeDeliveryHook(),
        );
        $this->expectException(DomainException::class);
        $service->enrollGroup(new CurrentUser(1, 'admin', 2), 3, 9);
    }

    public function testEnrollmentsCreatePendingDeliveriesForExistingActiveTasksOnce(): void
    {
        $repository = new FakeEnrollmentRepository();
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::exactly(2))->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        $taskRepository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public array $deliveries = [];

            public function createForEnrollment(int $studentId, int $subjectId): void
            {
                foreach ([
                    ['id' => $subjectId * 10, 'status' => 'active'],
                    ['id' => $subjectId * 10 + 1, 'status' => 'cancelled'],
                ] as $task) {
                    if ($task['status'] === 'active') {
                        $this->deliveries[$task['id'] . ':' . $studentId] = ['status' => 'pending'];
                    }
                }
            }
        };
        $hook = new TaskDeliveryEnrollmentHookAdapter(new TaskDeliveryService($taskRepository));
        $service = new EnrollmentService(
            $repository,
            new TransactionRunner($db),
            $hook,
        );
        $user = new CurrentUser(1, 'admin', 2);

        $service->enrollGroup($user, 3, 9);
        $service->enrollGroup($user, 3, 9);
        $service->enrollSubject($user, 21, 9);

        self::assertSame([
            '210:9' => ['status' => 'pending'],
            '220:9' => ['status' => 'pending'],
        ], $taskRepository->deliveries);
    }
}

final class FakeEnrollmentRepository extends EnrollmentRepository
{
    public array $enrollments = [];

    public function __construct(private bool $groupExists = true)
    {
    }

    public function group(int $schoolId, int $groupId): ?array
    {
        return $this->groupExists ? ['id' => $groupId] : null;
    }

    public function subject(int $schoolId, int $subjectId): ?array
    {
        return ['id' => $subjectId];
    }

    public function studentBelongsToSchool(int $schoolId, int $studentId): bool
    {
        return $schoolId === 2 && $studentId === 9;
    }

    public function groupSubjects(int $groupId, int $schoolId): array
    {
        return [['id' => 21], ['id' => 22]];
    }

    public function enroll(int $studentId, int $subjectId, ?int $groupId): void
    {
        $this->enrollments[] = [$studentId, $subjectId, $groupId];
    }
}

final class FakeDeliveryHook implements TaskDeliveryEnrollmentHook
{
    public array $calls = [];

    public function onStudentEnrolled(int $studentId, int $subjectId): void
    {
        $this->calls[] = [$studentId, $subjectId];
    }
}
