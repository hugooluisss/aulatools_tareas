<?php

declare(strict_types=1);

namespace App\Tasks\Controller;

use App\Auth\CurrentUser;
use App\Tasks\Service\TaskException;
use App\Tasks\Service\TaskService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TaskController
{
    public function __construct(private TaskService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function subjectTasks(ServerRequestInterface $request, int $subject_id): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->listSubjectTasks(
            $this->user($request),
            $subject_id,
            $this->number($query, 'page', 1),
            $this->number($query, 'per_page', 20),
        ));
    }

    public function create(ServerRequestInterface $request, int $subject_id): ResponseInterface
    {
        return $this->run(fn () => [
            'data' => $this->service->create($this->user($request), $subject_id, $this->body($request)),
        ], 201);
    }

    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => ['data' => $this->service->find($this->user($request), $id)]);
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => [
            'data' => $this->service->update($this->user($request), $id, $this->body($request)),
        ]);
    }

    public function cancel(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => ['data' => $this->service->cancel($this->user($request), $id)]);
    }

    public function deliveries(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->deliveries(
            $this->user($request),
            $id,
            isset($query['status']) ? (string) $query['status'] : null,
            $this->number($query, 'page', 1),
            $this->number($query, 'per_page', 20),
        ));
    }

    public function delivered(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => ['data' => $this->service->markDelivered($this->user($request), $id)]);
    }

    public function grade(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => [
            'data' => $this->service->grade($this->user($request), $id, $this->body($request)),
        ]);
    }

    public function myTasks(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->myTasks(
            $this->user($request),
            isset($query['status']) ? (string) $query['status'] : null,
            $this->number($query, 'page', 1),
            $this->number($query, 'per_page', 20),
        ));
    }

    public function myTaskDetail(ServerRequestInterface $request, int $delivery_id): ResponseInterface
    {
        return $this->run(fn () => ['data' => $this->service->myTaskDetail($this->user($request), $delivery_id)]);
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

    private function number(array $query, string $key, int $default): int
    {
        return filter_var($query[$key] ?? $default, FILTER_VALIDATE_INT) === false
            ? 0
            : (int) ($query[$key] ?? $default);
    }

    private function run(callable $action, int $success = 200): ResponseInterface
    {
        try {
            return $this->json($success, $action());
        } catch (TaskException $exception) {
            $code = match ($exception->status) {
                400 => 'VALIDATION_ERROR',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                422 => 'INVALID_STATE',
                default => 'INTERNAL_ERROR',
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
