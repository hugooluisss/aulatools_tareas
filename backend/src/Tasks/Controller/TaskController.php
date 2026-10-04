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
            isset($query['cycle_id']) ? $this->number($query, 'cycle_id', 0) : null,
            $this->number($query, 'page', 1),
            $this->number($query, 'per_page', 20),
        ));
    }

    public function overview(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->overview(
            $this->user($request),
            isset($query['search']) ? (string) $query['search'] : null,
            isset($query['status']) ? (string) $query['status'] : null,
        ));
    }

    public function statuses(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->deliveryStatuses($this->user($request)));
    }

    public function create(ServerRequestInterface $request, int $subject_id): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), $subject_id, $this->body($request)), 201);
    }

    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->find($this->user($request), $id));
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update($this->user($request), $id, $this->body($request)));
    }

    public function cancel(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->cancel($this->user($request), $id));
    }

    public function deliveries(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->deliveries(
            $this->user($request),
            $id,
            isset($query['search']) ? (string) $query['search'] : null,
            isset($query['status']) ? (string) $query['status'] : null,
            $this->number($query, 'page', 1),
            $this->number($query, 'per_page', 20),
        ));
    }

    public function undelivered(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->markUndelivered($this->user($request), $id));
    }

    public function delivered(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->markDelivered($this->user($request), $id));
    }

    public function grade(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->grade($this->user($request), $id, $this->body($request)));
    }

    public function myTasks(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->myTasks(
            $this->user($request),
            isset($query['status']) ? (string) $query['status'] : null,
            isset($query['search']) ? (string) $query['search'] : null,
            isset($query['cycle_id']) ? $this->number($query, 'cycle_id', 0) : null,
            $this->number($query, 'page', 1),
            $this->number($query, 'per_page', 20),
        ));
    }

    public function myTaskDetail(ServerRequestInterface $request, int $delivery_id): ResponseInterface
    {
        return $this->run(fn () => $this->service->myTaskDetail($this->user($request), $delivery_id));
    }

    public function history(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->history($this->user($request), $id));
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
                409 => 'CONFLICT',
                422 => 'INVALID_STATE',
                default => 'INTERNAL_ERROR',
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
