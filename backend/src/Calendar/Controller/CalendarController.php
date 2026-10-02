<?php

declare(strict_types=1);

namespace App\Calendar\Controller;

use App\Auth\CurrentUser;
use App\Calendar\Service\CalendarException;
use App\Calendar\Service\CalendarService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CalendarController
{
    public function __construct(private CalendarService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function view(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->view($this->user($request), $request->getQueryParams()));
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->listEvents($this->user($request), $request->getQueryParams()));
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => [
            'data' => $this->service->create($this->user($request), $this->body($request)),
        ], 201);
    }

    public function event(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => ['data' => $this->service->event($this->user($request), $id)]);
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => [
            'data' => $this->service->update($this->user($request), $id, $this->body($request)),
        ]);
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

    private function run(callable $action, int $status = 200): ResponseInterface
    {
        try {
            $result = $action();
            return $status === 204 ? $this->responses->createResponse(204) : $this->json($status, $result);
        } catch (CalendarException $exception) {
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
