<?php

declare(strict_types=1);

namespace App\TaskComments\Controller;

use App\Auth\CurrentUser;
use App\TaskComments\Service\CommentException;
use App\TaskComments\Service\CommentService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CommentController
{
    public function __construct(private CommentService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function index(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            $id,
            (int) ($query['page'] ?? 1),
            (int) ($query['per_page'] ?? 20),
        ));
    }

    public function create(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => [
            'data' => $this->service->create($this->user($request), $id, $this->body($request)),
        ], 201);
    }

    private function user(ServerRequestInterface $request): CurrentUser
    {
        return $request->getAttribute(CurrentUser::class);
    }

    private function body(ServerRequestInterface $request): array
    {
        $body = json_decode((string) $request->getBody(), true);
        return is_array($body) ? $body : [];
    }

    private function run(callable $action, int $status = 200): ResponseInterface
    {
        try {
            return $this->json($status, $action());
        } catch (CommentException $exception) {
            $code = match ($exception->status) {
                400 => 'VALIDATION_ERROR', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', default => 'INTERNAL_ERROR'
            };
            return $this->json($exception->status, [
                'error' => ['code' => $code, 'message' => $exception->getMessage()],
            ]);
        }
    }

    private function json(int $status, array $data): ResponseInterface
    {
        $response = $this->responses->createResponse($status)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
