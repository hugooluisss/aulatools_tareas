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
            $response = $this->responseFactory->createResponse(401)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write('{"error":"Unauthorized"}');
            return $response;
        }
        if (!in_array($user->role, $this->allowedRoles, true)) {
            $response = $this->responseFactory->createResponse(403)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write('{"error":"Forbidden"}');
            return $response;
        }
        return $handler->handle($request);
    }
}
