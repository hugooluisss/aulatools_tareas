<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Shared\TransactionRunner;
use App\Tasks\Controller\TaskController;
use App\Tasks\Repository\TaskRepository;
use App\Tasks\Service\TaskService;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

final class TaskOverviewControllerTest extends TestCase
{
    public function testOverviewReturnsAllRowsAndQueryParameters(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public array $arguments = [];

            public function overview(int $schoolId, ?string $search, array $statuses): array
            {
                $this->arguments = [$schoolId, $search, $statuses];
                return [];
            }
        };
        $controller = new TaskController(
            new TaskService(
                $repository,
                new TransactionRunner($this->createMock(ConnectionInterface::class)),
            ),
            new ResponseFactory(),
        );
        $request = (new ServerRequest())
            ->withAttribute(CurrentUser::class, new CurrentUser(1, 'admin', 4))
            ->withQueryParams(['search' => 'Math', 'status' => 'pending,graded']);

        $response = $controller->overview($request);
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([4, 'Math', ['pending', 'graded']], $repository->arguments);
        self::assertSame([], $body);
    }

    public function testOverviewMapsUnsupportedStatusToStandardBadRequest(): void
    {
        $controller = new TaskController(
            new TaskService(
                new TaskRepository($this->createMock(ConnectionInterface::class)),
                new TransactionRunner($this->createMock(ConnectionInterface::class)),
            ),
            new ResponseFactory(),
        );
        $request = (new ServerRequest())
            ->withAttribute(CurrentUser::class, new CurrentUser(1, 'admin', 4))
            ->withQueryParams(['status' => 'unknown']);

        $response = $controller->overview($request);
        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('VALIDATION_ERROR', $body['error']['code']);
    }

    public function testStatusesAreAvailableToAnyAuthenticatedRole(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends TaskRepository {
            public function deliveryStatuses(): array
            {
                return [[
                    'code' => 'pending',
                    'label' => 'Pendiente',
                    'color' => '#FFF3CD',
                    'text_color' => '#664D03',
                ]];
            }
        };
        $controller = new TaskController(
            new TaskService(
                $repository,
                new TransactionRunner($this->createMock(ConnectionInterface::class)),
            ),
            new ResponseFactory(),
        );
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(1, 'student', 4));

        $response = $controller->statuses($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            [[
                'code' => 'pending',
                'label' => 'Pendiente',
                'color' => '#FFF3CD',
                'text_color' => '#664D03',
            ]],
            json_decode((string) $response->getBody(), true),
        );
    }
}
