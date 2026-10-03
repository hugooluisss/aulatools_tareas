<?php

declare(strict_types=1);

namespace App\Enrollments\Controller;

use App\Auth\CurrentUser;
use App\Enrollments\Service\EnrollmentException;
use App\Enrollments\Service\EnrollmentService;
use App\Shared\JsonResponse;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class EnrollmentController
{
    public function __construct(
        private EnrollmentService $service,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            $this->optionalId($query, 'cycle_id'),
            $this->optionalId($query, 'student_id'),
        ));
    }

    public function aspirants(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->aspirants($this->user($request)));
    }

    public function reenrollable(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->reenrollable($this->user($request)));
    }

    public function bulk(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(function () use ($request): array {
            $body = $this->body($request);
            $ids = $body['student_ids'] ?? null;
            if (!is_array($ids) || array_filter($ids, static fn ($id) => filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1)) {
                throw new InvalidArgumentException('Invalid student_ids.');
            }
            if (!is_string($body['type'] ?? null)) {
                throw new InvalidArgumentException('Invalid type.');
            }
            return $this->service->bulk(
                $this->user($request),
                $body['type'],
                array_map('intval', $ids),
                $this->id($body, 'cycle_id'),
                $this->id($body, 'group_id'),
            );
        }, 201);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(function () use ($request): array {
            $body = $this->body($request);
            return $this->service->create(
                $this->user($request),
                $this->id($body, 'student_id'),
                $this->id($body, 'cycle_id'),
                $this->id($body, 'group_id'),
            );
        }, 201);
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update(
            $this->user($request),
            $id,
            $this->id($this->body($request), 'group_id'),
        ));
    }

    public function delete(ServerRequestInterface $request, int $id): ResponseInterface
    {
        try {
            $this->service->delete($this->user($request), $id);
            return $this->responseFactory->createResponse(204);
        } catch (EnrollmentException $exception) {
            return JsonResponse::error($this->responseFactory, $exception->status, $exception->getMessage());
        } catch (Throwable) {
            return JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }

    private function run(callable $action, int $status = 200): ResponseInterface
    {
        try {
            return JsonResponse::send($this->responseFactory, $status, $action());
        } catch (EnrollmentException $exception) {
            return JsonResponse::error($this->responseFactory, $exception->status, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::error($this->responseFactory, 400, $exception->getMessage());
        } catch (Throwable) {
            return JsonResponse::error($this->responseFactory, 500, 'Unexpected server error.');
        }
    }

    private function id(array $data, string $field): int
    {
        if (!isset($data[$field]) || filter_var($data[$field], FILTER_VALIDATE_INT) === false || (int) $data[$field] < 1) {
            throw new InvalidArgumentException("Invalid {$field}.");
        }
        return (int) $data[$field];
    }

    private function optionalId(array $data, string $field): ?int
    {
        return !array_key_exists($field, $data) || $data[$field] === '' ? null : $this->id($data, $field);
    }

    private function body(ServerRequestInterface $request): array
    {
        $body = json_decode((string) $request->getBody(), true);
        return is_array($body) ? $body : [];
    }

    private function user(ServerRequestInterface $request): CurrentUser
    {
        $user = $request->getAttribute(CurrentUser::class);
        if (!$user instanceof CurrentUser) {
            throw new EnrollmentException('Unauthorized.', 401);
        }
        return $user;
    }
}
