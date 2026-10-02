<?php

declare(strict_types=1);

namespace App\Cycles\Controller;

use App\Auth\CurrentUser;
use App\Cycles\Service\CycleException;
use App\Cycles\Service\CycleService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CycleController
{
    public function __construct(private CycleService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            (int) ($query['page'] ?? 1),
            (int) ($query['per_page'] ?? 20),
        ));
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), $this->body($request)), 201);
    }

    public function get(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->find($this->user($request), $id));
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update($this->user($request), $id, $this->body($request)));
    }

    public function finish(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->finish($this->user($request), $id));
    }

    private function run(callable $operation, int $status = 200): ResponseInterface
    {
        try {
            return $this->json($status, $operation());
        } catch (CycleException $exception) {
            $code = match ($exception->status) {
                400 => 'VALIDATION_ERROR',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                422 => 'INVALID_STATE',
                default => 'INTERNAL_ERROR',
            };
            return \App\Shared\JsonResponse::error($this->responses, $exception->status, $exception->getMessage(), $code);
        } catch (\Throwable) {
            return \App\Shared\JsonResponse::error($this->responses, 500, 'Unexpected server error.');
        }
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

    private function json(int $status, mixed $data): ResponseInterface
    {
        return \App\Shared\JsonResponse::send($this->responses, $status, $data);
    }
}
