<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use App\Auth\CurrentUser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoleRestrictionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private array $allowedRoles,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute(CurrentUser::class);
        if (!$user instanceof CurrentUser) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 401, 'Unauthorized.');
        }
        if (!in_array($user->role, $this->allowedRoles, true)) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 403, 'Forbidden.');
        }
        return $handler->handle($request);
    }
}
