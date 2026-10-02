<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Auth\Middleware\AuthenticationMiddleware;
use App\Auth\Middleware\RoleRestrictionMiddleware;
use App\Auth\Service\JwtService;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('JWT_SECRET=01234567890123456789012345678901');
    }

    public function testMissingOrInvalidTokenReturns401(): void
    {
        $middleware = new AuthenticationMiddleware(new JwtService(), new ResponseFactory());
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        self::assertSame(401, $middleware->process(new ServerRequest(), $handler)->getStatusCode());
        $request = (new ServerRequest())->withHeader('Authorization', 'Bearer invalid');
        self::assertSame(401, $middleware->process($request, $handler)->getStatusCode());
    }

    public function testRoleRestrictionReturns403AndAllowsPermittedRole(): void
    {
        $middleware = new RoleRestrictionMiddleware(['admin', 'teacher'], new ResponseFactory());
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn((new ResponseFactory())->createResponse(204));

        $studentRequest = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(1, 'student', 7));
        self::assertSame(403, $middleware->process($studentRequest, $handler)->getStatusCode());
        $teacherRequest = (new ServerRequest())->withAttribute(CurrentUser::class, new CurrentUser(2, 'teacher', 7));
        self::assertSame(204, $middleware->process($teacherRequest, $handler)->getStatusCode());
    }
}
