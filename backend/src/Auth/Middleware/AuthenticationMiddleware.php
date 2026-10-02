<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use App\Auth\CurrentUser;
use App\Auth\Service\JwtService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwtService,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return $this->unauthorized();
        }

        try {
            $claims = $this->jwtService->decode($matches[1]);
            if (!isset($claims->sub, $claims->role, $claims->school_id)) {
                return $this->unauthorized();
            }
            $user = new CurrentUser((int) $claims->sub, (string) $claims->role, (int) $claims->school_id);
        } catch (Throwable) {
            return $this->unauthorized();
        }

        return $handler->handle($request->withAttribute(CurrentUser::class, $user));
    }

    private function unauthorized(): ResponseInterface
    {
        return \App\Shared\JsonResponse::error($this->responseFactory, 401, 'Unauthorized.');
    }
}
