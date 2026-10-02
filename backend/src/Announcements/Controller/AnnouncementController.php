<?php

declare(strict_types=1);

namespace App\Announcements\Controller;

use App\Announcements\Service\AnnouncementException;
use App\Announcements\Service\AnnouncementService;
use App\Auth\CurrentUser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AnnouncementController
{
    public function __construct(private AnnouncementService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            $this->number($request, 'page', 1),
            $this->number($request, 'per_page', 20),
        ));
    }

    public function active(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->active(
            $this->user($request),
            $this->number($request, 'page', 1),
            $this->number($request, 'per_page', 20),
        ));
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), $this->body($request)), 201);
    }

    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->find($this->user($request), $id));
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update($this->user($request), $id, $this->body($request)));
    }

    public function delete(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(function () use ($request, $id) {
            $this->service->delete($this->user($request), $id);
            return null;
        }, 204);
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

    private function number(ServerRequestInterface $request, string $key, int $default): int
    {
        $query = $request->getQueryParams();
        return filter_var($query[$key] ?? $default, FILTER_VALIDATE_INT) === false
            ? 0
            : (int) ($query[$key] ?? $default);
    }

    private function run(callable $action, int $status = 200): ResponseInterface
    {
        try {
            $result = $action();
            return $status === 204 ? $this->responses->createResponse(204) : $this->json($status, $result);
        } catch (AnnouncementException $exception) {
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
