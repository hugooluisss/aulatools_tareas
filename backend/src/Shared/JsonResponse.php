<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class JsonResponse
{
    public static function send(ResponseFactoryInterface $factory, int $status, mixed $body): ResponseInterface
    {
        if ($status === 204) {
            return $factory->createResponse(204);
        }

        $response = $factory->createResponse($status)->withHeader('Content-Type', 'application/json');
        if (is_array($body) && array_key_exists('data', $body) && isset($body['meta']) && is_array($body['meta'])) {
            $meta = $body['meta'];
            $body = $body['data'];
            $response = $response
                ->withHeader('X-Total-Count', (string) ($meta['total'] ?? 0))
                ->withHeader('X-Page', (string) ($meta['page'] ?? 1))
                ->withHeader('X-Per-Page', (string) ($meta['per_page'] ?? 20));
        }
        $response->getBody()->write(json_encode($body, JSON_THROW_ON_ERROR));
        return $response;
    }

    public static function error(
        ResponseFactoryInterface $factory,
        int $status,
        string $message,
        ?string $code = null,
    ): ResponseInterface
    {
        $code ??= match ($status) {
            400 => 'VALIDATION_ERROR',
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'INVALID_STATE',
            default => 'INTERNAL_ERROR',
        };
        return self::send($factory, $status, [
            'error' => [
                'code' => $code,
                'message' => $status >= 500 ? 'Unexpected server error.' : $message,
            ],
        ]);
    }
}
