<?php

declare(strict_types=1);

namespace App\Subjects\Controller;

use App\Auth\CurrentUser;
use App\Subjects\Service\SubjectService;
use App\Subjects\Service\SubjectException;
use DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SubjectController
{
    public function __construct(private SubjectService $service, private ResponseFactoryInterface $responseFactory)
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            $query,
            $this->page($query, 'page', 1),
            $this->page($query, 'per_page', 20),
        ));
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), $this->body($request)), 201);
    }

    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->view($this->user($request), $id));
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

    public function students(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->students(
            $this->user($request),
            $id,
            $this->page($query, 'page', 1),
            $this->page($query, 'per_page', 20),
            isset($query['cycle_id']) && filter_var($query['cycle_id'], FILTER_VALIDATE_INT) !== false ? (int) $query['cycle_id'] : null,
        ));
    }

    private function user(ServerRequestInterface $request): CurrentUser
    {
        return $request->getAttribute(CurrentUser::class);
    }

    private function body(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);
        return is_array($data) ? $data : [];
    }

    private function page(array $query, string $name, int $default): int
    {
        return max(1, (int) ($query[$name] ?? $default));
    }

    private function run(callable $action, int $success = 200): ResponseInterface
    {
        try {
            $result = $action();
            if ($success === 204) {
                return $this->responseFactory->createResponse(204);
            }
            return $this->json($success, $result);
        } catch (SubjectException $exception) {
            return \App\Shared\JsonResponse::error($this->responseFactory, $exception->status, $exception->getMessage());
        } catch (DomainException $exception) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 404, $exception->getMessage());
        } catch (\InvalidArgumentException $exception) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 400, $exception->getMessage());
        } catch (\Throwable) {
            return \App\Shared\JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }

    private function json(int $status, mixed $data): ResponseInterface
    {
        return \App\Shared\JsonResponse::send($this->responseFactory, $status, $data);
    }
}
