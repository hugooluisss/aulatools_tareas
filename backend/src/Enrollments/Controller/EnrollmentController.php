<?php

declare(strict_types=1);

namespace App\Enrollments\Controller;

use App\Auth\CurrentUser;
use App\Enrollments\Service\EnrollmentService;
use DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EnrollmentController
{
    public function __construct(private EnrollmentService $service, private ResponseFactoryInterface $responseFactory)
    {
    }

    public function enrollGroup(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $body = $this->body($request);
        return $this->run(fn () => $this->service->enrollGroup($this->user($request), $id, $this->studentId($body)), 201);
    }

    public function enrollSubject(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $body = $this->body($request);
        return $this->run(fn () => $this->service->enrollSubject($this->user($request), $id, $this->studentId($body)), 201);
    }

    public function unenrollSubject(ServerRequestInterface $request, int $id, int $student_id): ResponseInterface
    {
        return $this->run(function () use ($request, $id, $student_id) {
            $this->service->unenrollSubject($this->user($request), $id, $student_id);
            return null;
        }, 204);
    }

    private function studentId(array $body): int
    {
        if (
            !isset($body['student_id'])
            || filter_var($body['student_id'], FILTER_VALIDATE_INT) === false
            || (int) $body['student_id'] < 1
        ) {
            throw new \InvalidArgumentException('Invalid student id.');
        }
        return (int) $body['student_id'];
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

    private function run(callable $action, int $success): ResponseInterface
    {
        try {
            $data = $action();
            if ($success === 204) {
                return $this->responseFactory->createResponse(204);
            }
            return $this->json($success, $data);
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
