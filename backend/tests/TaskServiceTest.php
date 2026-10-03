<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Shared\TransactionRunner;
use App\Tasks\Controller\TaskController;
use App\Tasks\Repository\TaskRepository;
use App\Tasks\Service\TaskDeliveryService;
use App\Tasks\Service\TaskException;
use App\Tasks\Service\TaskService;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class TaskServiceTest extends TestCase
{
    public function testCreatingTaskAndEnrollmentCreatePendingDeliveries(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public array $created = [];
            public array $enrollment = [];

            public function subject(int $schoolId, int $subjectId): ?array
            {
                return ['id' => $subjectId, 'teacher_id' => 7];
            }

            public function cycle(int $schoolId, int $cycleId): ?array
            {
                return ['id' => $cycleId, 'status' => 'active'];
            }

            public function create(int $subjectId, int $cycleId, array $data): int
            {
                $this->created = [$subjectId, $cycleId, $data];
                return 9;
            }

            public function find(int $schoolId, int $taskId): ?array
            {
                return [
                    'id' => $taskId,
                    'subject_id' => 4,
                    'cycle_id' => 14,
                    'name' => 'Essay',
                    'description' => 'Write',
                    'due_at' => '2026-10-08',
                    'status' => 'active',
                    'teacher_id' => 7,
                    'teacher_user_id' => 7,
                    'teacher_first_name' => 'A',
                    'teacher_last_name' => 'Teacher',
                ];
            }

            public function createForEnrollment(int $studentId, int $subjectId, int $cycleId): void
            {
                $this->enrollment = [$studentId, $subjectId, $cycleId];
            }
        };
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        $service = new TaskService($repository, new TransactionRunner($db));

        $task = $service->create(
            new CurrentUser(7, 'teacher', 2),
            4,
            ['name' => ' Essay ', 'description' => 'Write', 'due_at' => '2026-10-08', 'cycle_id' => 14],
        );
        (new TaskDeliveryService($repository))->createForEnrollment(15, 4, 14);

        self::assertSame('Essay', $repository->created[2]['name']);
        self::assertSame('2026-10-08', $repository->created[2]['due_at']);
        self::assertSame([15, 4, 14], $repository->enrollment);
        self::assertSame(9, $task['id']);
    }

    public function testGradeMustBeInRangeAndDeliveryMustBeDelivered(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'task_id' => 3, 'status' => 'pending', 'teacher_id' => 8];
            }
        };
        $service = new TaskService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
        );

        try {
            $service->grade(new CurrentUser(8, 'teacher', 2), 5, ['grade' => 101]);
            self::fail('Out of range grade was accepted.');
        } catch (TaskException $exception) {
            self::assertSame(400, $exception->status);
        }

        try {
            $service->grade(new CurrentUser(8, 'teacher', 2), 5, ['grade' => 80]);
            self::fail('Pending delivery was graded.');
        } catch (TaskException $exception) {
            self::assertSame(422, $exception->status);
        }
    }

    public function testStudentCannotAccessAnotherStudentsDelivery(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public function myTaskDetail(int $schoolId, int $studentId, int $deliveryId): ?array
            {
                return null;
            }
        };
        $controller = new TaskController(
            new TaskService(
                $repository,
                new TransactionRunner($this->createMock(ConnectionInterface::class)),
            ),
            new ResponseFactory(),
        );
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(10, 'student', 2));

        $response = $controller->myTaskDetail($request, 55);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('NOT_FOUND', (string) $response->getBody());
    }

    public function testMyTasksDefaultsToPending(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public ?string $status = null;

            public function myTasks(int $schoolId, int $studentId, string $status, int $offset, int $limit): array
            {
                $this->status = $status;
                return ['data' => [], 'total' => 0];
            }
        };
        $service = new TaskService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
        );

        $service->myTasks(new CurrentUser(10, 'student', 2), null, 1, 20);

        self::assertSame('pending', $repository->status);
    }

    public function testOverviewUsesSchoolScopeFiltersAndReturnsAllRows(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public array $arguments = [];

            public function overview(int $schoolId, ?string $search, array $statuses): array
            {
                $this->arguments = [$schoolId, $search, $statuses];
                return [[
                        'delivery_id' => '31',
                        'status' => 'graded',
                        'task_id' => '7',
                        'task_title' => 'Essay',
                        'task_description' => 'Write an essay.',
                        'due_at' => '2026-10-08',
                        'student_id' => '15',
                        'student_first_name' => 'Ada',
                        'student_last_name' => 'Lovelace',
                        'subject_id' => '4',
                        'subject_code' => 'MAT1',
                        'subject_name' => 'Math',
                    ]];
            }
        };
        $service = new TaskService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
        );

        $result = $service->overview(new CurrentUser(1, 'admin', 9), ' Ada ', 'pending,graded');

        self::assertSame([9, 'Ada', ['pending', 'graded']], $repository->arguments);
        self::assertSame([
            'delivery_id' => 31,
            'status' => 'graded',
            'task' => [
                'id' => 7,
                'title' => 'Essay',
                'description' => 'Write an essay.',
                'due_at' => '2026-10-08',
            ],
            'student' => ['id' => 15, 'first_name' => 'Ada', 'last_name' => 'Lovelace'],
            'subject' => ['id' => 4, 'code' => 'MAT1', 'name' => 'Math'],
        ], $result[0]);
    }

    public function testOverviewRejectsNonAdminAndUnsupportedStatus(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public function overview(int $schoolId, ?string $search, array $statuses): array
            {
                self::fail('Overview repository should not be called for invalid access or status.');
            }
        };
        $service = new TaskService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
        );

        try {
            $service->overview(new CurrentUser(2, 'teacher', 9), null, null);
            self::fail('Non-admin access was accepted.');
        } catch (TaskException $exception) {
            self::assertSame(403, $exception->status);
        }

        try {
            $service->overview(new CurrentUser(1, 'admin', 9), null, 'graded,unknown');
            self::fail('Unsupported delivery status was accepted.');
        } catch (TaskException $exception) {
            self::assertSame(400, $exception->status);
        }
    }

    public function testDueDateRejectsTimestampInput(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public function subject(int $schoolId, int $subjectId): ?array
            {
                return ['id' => $subjectId, 'teacher_id' => 7];
            }

            public function cycle(int $schoolId, int $cycleId): ?array
            {
                return ['id' => $cycleId, 'status' => 'active'];
            }

            public function create(int $subjectId, int $cycleId, array $data): int
            {
                self::fail('Timestamp due_at reached repository.');
            }
        };
        $service = new TaskService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
        );

        try {
            $service->create(new CurrentUser(7, 'teacher', 2), 4, [
                'name' => 'Essay',
                'description' => 'Write',
                'due_at' => '2026-10-08T12:00:00Z',
                'cycle_id' => 14,
            ]);
            self::fail('Timestamp due_at was accepted.');
        } catch (TaskException $exception) {
            self::assertSame(400, $exception->status);
        }
    }

    public function testCancelTaskUpdatesAllDeliveriesInTransaction(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public bool $cancelled = false;

            public function find(int $schoolId, int $taskId): ?array
            {
                return [
                    'id' => $taskId,
                    'subject_id' => 4,
                    'cycle_id' => 14,
                    'name' => 'Essay',
                    'description' => 'Write',
                    'due_at' => '2026-10-08',
                    'status' => $this->cancelled ? 'cancelled' : 'active',
                    'teacher_id' => 7,
                    'teacher_user_id' => 7,
                    'teacher_first_name' => 'A',
                    'teacher_last_name' => 'Teacher',
                ];
            }

            public function cancel(int $taskId): void
            {
                $this->cancelled = true;
            }
        };
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);

        $task = (new TaskService($repository, new TransactionRunner($db)))
            ->cancel(new CurrentUser(7, 'teacher', 2), 9);

        self::assertTrue($repository->cancelled);
        self::assertSame('cancelled', $task['status']);
    }

    public function testMarkDeliveredSetsTimestampAndGradeChangesState(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public string $status = 'pending';
            public ?float $grade = null;

            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'task_id' => 3, 'status' => $this->status, 'teacher_id' => 8];
            }

            public function markDelivered(int $deliveryId): void
            {
                $this->status = 'delivered';
            }

            public function grade(int $deliveryId, int|float $grade): void
            {
                $this->status = 'graded';
                $this->grade = (float) $grade;
            }

            public function deliveryView(int $schoolId, int $deliveryId): ?array
            {
                return [
                    'id' => $deliveryId,
                    'task_id' => 3,
                    'student_id' => 10,
                    'status' => $this->status,
                    'delivered_at' => '2026-10-01 12:00:00',
                    'grade' => $this->grade,
                    'overdue' => 0,
                    'on_time' => 1,
                ];
            }
        };
        $service = new TaskService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
        );
        $teacher = new CurrentUser(8, 'teacher', 2);

        $delivered = $service->markDelivered($teacher, 5);
        $graded = $service->grade($teacher, 5, ['grade' => 90]);

        self::assertSame('delivered', $delivered['status']);
        self::assertSame('graded', $graded['status']);
        self::assertSame(90.0, $graded['grade']);
    }
}
