<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\TaskComments\Controller\CommentController;
use App\TaskComments\Repository\CommentRepository;
use App\TaskComments\Service\CommentException;
use App\TaskComments\Service\CommentService;
use App\Shared\TransactionRunner;
use App\Tasks\Repository\TaskDeliveryEventRepository;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class CommentServiceTest extends TestCase
{
    public function testOwnerCanCommentOnOwnDelivery(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'student_id' => 3, 'teacher_id' => 4];
            }

            public function create(int $deliveryId, int $authorId, string $body): int
            {
                return 11;
            }

            public function find(int $commentId): ?array
            {
                return [
                    'id' => $commentId,
                    'delivery_id' => 6,
                    'body' => 'Question',
                    'created_at' => '2026-10-01 10:00:00',
                    'author_id' => 3,
                    'first_name' => 'Sam',
                    'last_name' => 'Student',
                    'role' => 'student',
                ];
            }
        };
        $comment = (new CommentService($repository))->create(
            new CurrentUser(3, 'student', 2),
            6,
            ['body' => ' Question '],
        );

        self::assertSame('Question', $comment['body']);
        self::assertSame('student', $comment['author']['role']);
    }

    public function testCommentAddedEventIncludesActorAndCommentId(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'task_id' => 12, 'student_id' => 3, 'teacher_id' => 4];
            }

            public function create(int $deliveryId, int $authorId, string $body): int
            {
                return 47;
            }

            public function find(int $commentId): ?array
            {
                return ['id' => $commentId, 'delivery_id' => 6, 'body' => 'Question', 'created_at' => '2026-10-01 10:00:00', 'author_id' => 3, 'first_name' => 'Sam', 'last_name' => 'Student', 'role' => 'student'];
            }
        };
        $events = new class ($this->createMock(ConnectionInterface::class)) extends TaskDeliveryEventRepository {
            public array $record = [];

            public function add(int $deliveryId, int $taskId, string $type, ?CurrentUser $actor, array $payload): void
            {
                $this->record = [$deliveryId, $taskId, $type, $actor?->id, $actor?->role, $payload];
            }
        };
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        (new CommentService($repository, new TransactionRunner($db), $events))->create(
            new CurrentUser(3, 'student', 2),
            6,
            ['body' => 'Question'],
        );
        self::assertSame([6, 12, 'comment_added', 3, 'student', ['comment_id' => 47]], $events->record);
    }

    public function testCommentEventFailureRollsBackCommentCreation(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public bool $created = false;

            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'task_id' => 12, 'student_id' => 3, 'teacher_id' => 4];
            }

            public function create(int $deliveryId, int $authorId, string $body): int
            {
                $this->created = true;
                return 47;
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
            (new CommentService($repository, new TransactionRunner($db), $events))->create(
                new CurrentUser(3, 'student', 2),
                6,
                ['body' => 'Question'],
            );
            self::fail('Failed event write did not fail comment creation.');
        } catch (\RuntimeException $exception) {
            self::assertSame('event insert failed', $exception->getMessage());
        }
        self::assertTrue($repository->created);
    }

    public function testOtherStudentGets404ForPrivateThread(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'student_id' => 3, 'teacher_id' => 4];
            }
        };
        $controller = new CommentController(new CommentService($repository), new ResponseFactory());
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(99, 'student', 2));

        $response = $controller->index($request, 6);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testTeacherWhoDoesNotOwnSubjectGets403(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'student_id' => 3, 'teacher_id' => 4];
            }
        };

        try {
            (new CommentService($repository))->list(new CurrentUser(20, 'teacher', 2), 6, 1, 20);
            self::fail('Unassigned teacher accessed comment thread.');
        } catch (CommentException $exception) {
            self::assertSame(403, $exception->status);
        }
    }

    public function testCommentMustBeNonemptyAndNoLongerThan2000Characters(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'student_id' => 3, 'teacher_id' => 4];
            }
        };
        $service = new CommentService($repository);

        foreach (['   ', str_repeat('á', 2001)] as $body) {
            try {
                $service->create(new CurrentUser(3, 'student', 2), 6, ['body' => $body]);
                self::fail('Invalid comment was accepted.');
            } catch (CommentException $exception) {
                self::assertSame(400, $exception->status);
            }
        }
    }

    public function testAssignedTeacherAndAdminCanMarkReadButStudentCannot(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public array $reads = [];

            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'student_id' => 3, 'teacher_id' => 4];
            }

            public function markRead(int $userId, int $deliveryId): void
            {
                $this->reads[] = [$userId, $deliveryId];
            }
        };
        $service = new CommentService($repository);

        $service->markRead(new CurrentUser(4, 'teacher', 2), 6);
        $service->markRead(new CurrentUser(8, 'admin', 2), 6);

        self::assertSame([[4, 6], [8, 6]], $repository->reads);
        try {
            $service->markRead(new CurrentUser(3, 'student', 2), 6);
            self::fail('Student was allowed to update a teacher read cursor.');
        } catch (CommentException $exception) {
            self::assertSame(403, $exception->status);
        }
    }

    public function testMarkReadControllerReturnsNoContent(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'student_id' => 3, 'teacher_id' => 4];
            }

            public function markRead(int $userId, int $deliveryId): void
            {
            }
        };
        $controller = new CommentController(new CommentService($repository), new ResponseFactory());
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(4, 'teacher', 2));

        $response = $controller->markRead($request, 6);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testMarkReadRejectsUnassignedTeacher(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends CommentRepository {
            public function delivery(int $schoolId, int $deliveryId): ?array
            {
                return ['id' => $deliveryId, 'student_id' => 3, 'teacher_id' => 4];
            }
        };
        $controller = new CommentController(new CommentService($repository), new ResponseFactory());
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(20, 'teacher', 2));

        self::assertSame(403, $controller->markRead($request, 6)->getStatusCode());
    }
}
