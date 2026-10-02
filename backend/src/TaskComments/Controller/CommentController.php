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
        return $this->run(fn () => $this->service->create($this->user($request), $id, $this->body($request)), 201);
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
                400 => 'VALIDATION_ERROR', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 409 => 'CONFLICT', 422 => 'INVALID_STATE', default => 'INTERNAL_ERROR'
            };
            return \App\Shared\JsonResponse::error($this->responses, $exception->status, $exception->getMessage(), $code);
        } catch (\Throwable) {
            return \App\Shared\JsonResponse::error($this->responses, 500, 'Unexpected server error.');
        }
    }

    private function json(int $status, mixed $data): ResponseInterface
    {
        return \App\Shared\JsonResponse::send($this->responses, $status, $data);
    }
}
