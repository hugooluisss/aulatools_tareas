<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Shared\TransactionRunner;
use App\Tasks\Controller\TaskController;
use App\Tasks\Repository\TaskRepository;
use App\Tasks\Repository\TaskDeliveryEventRepository;
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
    public function testTaskCreateRecordsTaskCreatedForEachDeliveryAndRollsBackTogether(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public array $deliveries = [];

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
                $this->deliveries = [31, 32];
                return 9;
            }

            public function find(int $schoolId, int $taskId): ?array
            {
                return ['id' => $taskId, 'subject_id' => 4, 'cycle_id' => 14, 'name' => 'Essay', 'description' => 'Write', 'due_at' => '2026-10-08', 'status' => 'active', 'teacher_id' => 7, 'teacher_user_id' => 7, 'teacher_first_name' => 'A', 'teacher_last_name' => 'Teacher'];
            }
        };
        $events = new RecordingTaskEvents($this->createMock(ConnectionInterface::class));
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        $task = (new TaskService($repository, new TransactionRunner($db), $events))->create(
            new CurrentUser(7, 'teacher', 2),
            4,
            ['name' => 'Essay', 'description' => 'Write', 'due_at' => '2026-10-08', 'cycle_id' => 14],
        );
        self::assertSame(9, $task['id']);
        self::assertSame([
            [31, 9, 'task_created', 7, []],
            [32, 9, 'task_created', 7, []],
        ], $events->records);
    }

    public function testTaskCreationEventFailureRollsBackTaskCreation(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public bool $created = false;

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
                $this->created = true;
                return 9;
            }
        };
        $events = new RecordingTaskEvents($this->createMock(ConnectionInterface::class));
        $events->failOnAdd = true;
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('rollBack');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        try {
            (new TaskService($repository, new TransactionRunner($db), $events))->create(
                new CurrentUser(7, 'teacher', 2),
                4,
                ['name' => 'Essay', 'description' => 'Write', 'due_at' => '2026-10-08', 'cycle_id' => 14],
            );
            self::fail('Failed event write did not fail task creation.');
        } catch (\RuntimeException $exception) {
            self::assertSame('event insert failed', $exception->getMessage());
        }
        self::assertTrue($repository->created);
    }

    public function testUpdateAndCancelRecordActorAndStatusPayloads(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public bool $cancelled = false;

            public function find(int $schoolId, int $taskId): ?array
            {
                return ['id' => $taskId, 'subject_id' => 4, 'cycle_id' => 14, 'name' => $this->cancelled ? 'New' : 'Old', 'description' => 'Write', 'due_at' => '2026-10-08', 'status' => $this->cancelled ? 'cancelled' : 'active', 'teacher_id' => 7, 'teacher_user_id' => 7, 'teacher_first_name' => 'A', 'teacher_last_name' => 'Teacher'];
            }

            public function update(int $taskId, array $data): void
            {
            }

            public function deliveriesForTask(int $taskId): array
            {
                return [['id' => 31, 'status' => 'pending'], ['id' => 32, 'status' => 'delivered']];
            }

            public function cancel(int $taskId): void
            {
                $this->cancelled = true;
            }
        };
        $events = new RecordingTaskEvents($this->createMock(ConnectionInterface::class));
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($this->createMock(TransactionInterface::class));
        $service = new TaskService($repository, new TransactionRunner($db), $events);
        $teacher = new CurrentUser(7, 'teacher', 2);
        $service->update($teacher, 9, ['name' => 'New', 'description' => 'Write 2', 'due_at' => '2026-10-09']);
        $service->cancel($teacher, 9);
        self::assertSame('task_updated', $events->records[0][2]);
        self::assertSame(7, $events->records[0][3]);
        self::assertSame('Old', $events->records[0][4]['old']['name']);
        self::assertSame('New', $events->records[0][4]['new']['name']);
        self::assertSame(['status_changed', 7, ['old_status' => 'pending', 'new_status' => 'cancelled']], array_slice($events->records[2], 2));
        self::assertSame(['status_changed', 7, ['old_status' => 'delivered', 'new_status' => 'cancelled']], array_slice($events->records[3], 2));
    }

    public function testDeliveryActionsRecordEventsAndRollbackWhenEventFails(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public string $status = 'pending';
            public ?float $grade = null;

            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'task_id' => 3, 'status' => $this->status, 'grade' => $this->grade, 'teacher_id' => 8];
            }

            public function markDelivered(int $deliveryId): void
            {
                $this->status = 'delivered';
            }

            public function markUndelivered(int $deliveryId): void
            {
                $this->status = 'pending';
                $this->grade = null;
            }

            public function grade(int $deliveryId, int|float $grade): void
            {
                $this->status = 'graded';
                $this->grade = (float) $grade;
            }

            public function deliveryView(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'task_id' => 3, 'student_id' => 10, 'status' => $this->status, 'delivered_at' => null, 'grade' => $this->grade, 'overdue' => 0, 'on_time' => 0];
            }
        };
        $events = new RecordingTaskEvents($this->createMock(ConnectionInterface::class));
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($this->createMock(TransactionInterface::class));
        $service = new TaskService($repository, new TransactionRunner($db), $events);
        $teacher = new CurrentUser(8, 'teacher', 2);
        $service->markDelivered($teacher, 5);
        $service->markUndelivered($teacher, 5);
        $repository->status = 'delivered';
        $service->grade($teacher, 5, ['grade' => 80]);
        $repository->status = 'delivered';
        $service->grade($teacher, 5, ['grade' => 90]);
        self::assertSame(['delivered', 'status_changed', 'undelivered', 'status_changed', 'graded', 'status_changed', 'regrade', 'status_changed'], array_column($events->records, 2));
        self::assertSame(8, $events->records[0][3]);
        self::assertSame(['old_status' => 'pending', 'new_status' => 'delivered'], $events->records[0][4]);
        self::assertSame(['old_status' => 'delivered', 'new_status' => 'pending'], $events->records[2][4]);
        self::assertSame(['old_grade' => null, 'new_grade' => 80.0], $events->records[4][4]);
        self::assertSame(['old_grade' => 80.0, 'new_grade' => 90.0], $events->records[6][4]);
    }

    public function testEventFailureRollsBackDeliveryMutation(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public bool $changed = false;

            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'task_id' => 3, 'status' => 'pending', 'grade' => null, 'teacher_id' => 8];
            }

            public function markDelivered(int $deliveryId): void
            {
                $this->changed = true;
            }
        };
        $events = new class ($this->createMock(ConnectionInterface::class)) extends TaskDeliveryEventRepository {
            public function add(int $deliveryId, int $taskId, string $type, ?CurrentUser $actor, array $payload): void
            {
                throw new \RuntimeException('event insert failed');
            }
        };
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('rollBack');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        try {
            (new TaskService($repository, new TransactionRunner($db), $events))->markDelivered(new CurrentUser(8, 'teacher', 2), 5);
            self::fail('Failed event write did not fail delivery mutation.');
        } catch (\RuntimeException $exception) {
            self::assertSame('event insert failed', $exception->getMessage());
        }
        self::assertTrue($repository->changed);
    }

    public function testHistoryAuthorizationForOwnerTeacherAndAdmin(): void
    {
        $events = new class ($this->createMock(ConnectionInterface::class)) extends TaskDeliveryEventRepository {
            public function list(int $schoolId, int $deliveryId): ?array
            {
                return ['delivery' => ['student_id' => 10, 'teacher_id' => 20], 'items' => []];
            }
        };
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
        };
        $controller = new TaskController(
            new TaskService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class)), $events),
            new ResponseFactory(),
        );
        foreach ([
            [new CurrentUser(10, 'student', 2), 200],
            [new CurrentUser(11, 'student', 2), 404],
            [new CurrentUser(20, 'teacher', 2), 200],
            [new CurrentUser(21, 'teacher', 2), 403],
            [new CurrentUser(30, 'admin', 2), 200],
        ] as [$user, $status]) {
            $request = (new ServerRequest())->withAttribute(CurrentUser::class, $user);
            self::assertSame($status, $controller->history($request, 5)->getStatusCode());
        }
    }

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

    public function testMyTasksDefaultsToAllStatusesAndPassesSearchAndCycle(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public array $arguments = [];

            public function myTasks(int $schoolId, int $studentId, array $statuses, ?string $search, ?int $cycleId, int $offset, int $limit): array
            {
                $this->arguments = [$schoolId, $studentId, $statuses, $search, $cycleId, $offset, $limit];
                return ['data' => [], 'total' => 0];
            }
        };
        $service = new TaskService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
        );

        $service->myTasks(new CurrentUser(10, 'student', 2), null, '  math ', null, 1, 20);

        self::assertSame([2, 10, [], 'math', null, 0, 20], $repository->arguments);
        $service->myTasks(new CurrentUser(10, 'student', 2), 'pending,graded', null, 14, 2, 10);
        self::assertSame([2, 10, ['pending', 'graded'], null, 14, 10, 10], $repository->arguments);
    }

    public function testMyTasksRejectsUnsupportedStatus(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public function myTasks(int $schoolId, int $studentId, array $statuses, ?string $search, ?int $cycleId, int $offset, int $limit): array
            {
                self::fail('Repository should not be called for an unsupported status.');
            }
        };
        $service = new TaskService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class)));

        try {
            $service->myTasks(new CurrentUser(10, 'student', 2), 'pending,unknown', null, null, 1, 20);
            self::fail('Unsupported delivery status was accepted.');
        } catch (TaskException $exception) {
            self::assertSame(400, $exception->status);
        }
    }

    public function testDeliveriesAcceptsSearchAndMultipleStatuses(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public array $arguments = [];

            public function find(int $schoolId, int $taskId): ?array
            {
                return ['id' => $taskId, 'teacher_id' => 8];
            }

            public function deliveries(int $taskId, ?string $search, array $statuses, int $offset, int $limit, int $userId): array
            {
                $this->arguments = [$taskId, $search, $statuses, $offset, $limit, $userId];
                return ['data' => [], 'total' => 0];
            }
        };
        $controller = new TaskController(
            new TaskService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class))),
            new ResponseFactory(),
        );
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(8, 'teacher', 2));
        $request = $request->withQueryParams(['search' => ' Ana ', 'status' => 'pending,graded', 'page' => '2']);

        $response = $controller->deliveries($request, 17);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([17, 'Ana', ['pending', 'graded'], 20, 20, 8], $repository->arguments);
        self::assertStringContainsString('"total_pages":1', (string) $response->getBody());
    }

    public function testDeliveriesRejectsUnsupportedStatus(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public function find(int $schoolId, int $taskId): ?array
            {
                return ['id' => $taskId, 'teacher_id' => 8];
            }

            public function deliveries(int $taskId, ?string $search, array $statuses, int $offset, int $limit, int $userId): array
            {
                self::fail('Repository should not be called for an unsupported status.');
            }
        };
        $controller = new TaskController(
            new TaskService($repository, new TransactionRunner($this->createMock(ConnectionInterface::class))),
            new ResponseFactory(),
        );
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(8, 'teacher', 2));
        $request = $request->withQueryParams(['status' => 'pending,unknown']);

        $response = $controller->deliveries($request, 17);

        self::assertSame(400, $response->getStatusCode());
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

            public function markUndelivered(int $deliveryId): void
            {
                $this->status = 'pending';
                $this->grade = null;
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

        $undelivered = $service->markUndelivered($teacher, 5);

        self::assertSame('pending', $undelivered['status']);
        self::assertNull($undelivered['grade']);
        $this->expectException(TaskException::class);
        $service->markUndelivered($teacher, 5);
    }
}

final class RecordingTaskEvents extends TaskDeliveryEventRepository
{
    public array $records = [];
    public array $deliveryIds = [31, 32];
    public bool $failOnAdd = false;

    public function add(int $deliveryId, int $taskId, string $type, ?CurrentUser $actor, array $payload): void
    {
        if ($this->failOnAdd) {
            throw new \RuntimeException('event insert failed');
        }
        $this->records[] = [$deliveryId, $taskId, $type, $actor?->id, $payload];
    }

    public function createdForTask(int $taskId, ?CurrentUser $actor): void
    {
        foreach ($this->deliveryIds as $deliveryId) {
            $this->add($deliveryId, $taskId, 'task_created', $actor, []);
        }
    }
}
