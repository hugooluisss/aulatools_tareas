<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Auth\Service\PasswordHasher;
use App\Shared\TransactionRunner;
use App\Users\Controller\UserController;
use App\Users\Repository\UserRepository;
use App\Users\Service\UserService;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

final class UserAccessIntegrationTest extends TestCase
{
    public function testCrossSchoolTeacherRequestReturns404(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends UserRepository {
            public function find(int $schoolId, string $role, int $id): ?array
            {
                return null;
            }
        };
        $service = new UserService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new PasswordHasher(),
        );
        $controller = new UserController($service, new ResponseFactory());
        $request = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(1, 'admin', 12));

        $response = $controller->getTeacher($request, 88);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('NOT_FOUND', (string) $response->getBody());
    }
}
