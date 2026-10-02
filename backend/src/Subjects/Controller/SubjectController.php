<?php

declare(strict_types=1);

namespace App\Subjects\Controller;

use App\Auth\CurrentUser;
use App\Subjects\Service\SubjectService;
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
        return $this->run(fn () => [
            'data' => $this->service->create($this->user($request), $this->body($request)),
        ], 201);
    }

    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => ['data' => $this->service->view($this->user($request), $id)]);
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

    public function students(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->students(
            $this->user($request),
            $id,
            $this->page($query, 'page', 1),
            $this->page($query, 'per_page', 20),
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
        } catch (DomainException $exception) {
            return $this->json(404, ['error' => ['code' => 'NOT_FOUND', 'message' => $exception->getMessage()]]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(400, ['error' => ['code' => 'VALIDATION_ERROR', 'message' => $exception->getMessage()]]);
        }
    }

    private function json(int $status, array $data): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
