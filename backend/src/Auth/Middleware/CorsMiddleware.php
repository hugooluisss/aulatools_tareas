<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    private const ALLOWED_ORIGINS = [
        'https://agents-dev.hugosantiago.dev',
        'http://localhost:4200',
        'http://localhost:4330',
    ];

    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $request->getMethod() === 'OPTIONS'
            ? $this->responseFactory->createResponse(204)
            : $handler->handle($request);
        $origin = $request->getHeaderLine('Origin');
        if (in_array($origin, self::ALLOWED_ORIGINS, true)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        return $response
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
            ->withHeader('Access-Control-Expose-Headers', 'X-Total-Count, X-Page, X-Per-Page')
            ->withAddedHeader('Vary', 'Origin');
    }
}
