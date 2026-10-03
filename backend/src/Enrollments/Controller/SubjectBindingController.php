<?php

declare(strict_types=1);

namespace App\Enrollments\Controller;

use App\Auth\CurrentUser;
use App\Enrollments\Service\SubjectBindingService;
use App\Enrollments\Service\EnrollmentException;
use DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SubjectBindingController
{
    public function __construct(private SubjectBindingService $service, private ResponseFactoryInterface $responseFactory)
    {
    }

    public function enrollSubject(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $body = $this->body($request);
        return $this->run(function () use ($request, $id, $body): array {
            $cycleId = $body['cycle_id'] ?? null;
            if (filter_var($cycleId, FILTER_VALIDATE_INT) === false || (int) $cycleId < 1) {
                throw new \InvalidArgumentException('Invalid cycle_id.');
            }
            return $this->service->enrollSubject($this->user($request), $id, $this->studentId($body), (int) $cycleId);
        }, 201);
    }

    public function enrollSubjectBulk(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(function () use ($request, $id): array {
            $body = $this->body($request);
            $ids = $body['student_ids'] ?? null;
            $cycleId = $body['cycle_id'] ?? null;
            if (filter_var($cycleId, FILTER_VALIDATE_INT) === false || (int) $cycleId < 1) {
                throw new \InvalidArgumentException('Invalid cycle_id.');
            }
            if (!is_array($ids) || $ids === [] || array_filter(
                $ids,
                static fn ($studentId): bool => filter_var($studentId, FILTER_VALIDATE_INT) === false || (int) $studentId < 1,
            )) {
                throw new \InvalidArgumentException('Invalid student_ids.');
            }
            return $this->service->enrollSubjectsBulk($this->user($request), $id, (int) $cycleId, array_map('intval', $ids));
        }, 201);
    }

    public function reassignTeachers(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(function () use ($request, $id): array {
            $body = $this->body($request);
            $cycleId = $body['cycle_id'] ?? null;
            $teacherId = $body['teacher_id'] ?? null;
            $ids = $body['student_ids'] ?? null;
            if (filter_var($cycleId, FILTER_VALIDATE_INT) === false || (int) $cycleId < 1) {
                throw new \InvalidArgumentException('Invalid cycle_id.');
            }
            if (filter_var($teacherId, FILTER_VALIDATE_INT) === false || (int) $teacherId < 1) {
                throw new \InvalidArgumentException('Invalid teacher_id.');
            }
            if (!is_array($ids) || $ids === [] || array_filter($ids, static fn ($studentId): bool => filter_var($studentId, FILTER_VALIDATE_INT) === false || (int) $studentId < 1)) {
                throw new \InvalidArgumentException('Invalid student_ids.');
            }
            return $this->service->reassignTeachers($this->user($request), $id, (int) $cycleId, array_map('intval', $ids), (int) $teacherId);
        }, 200);
    }

    public function unenrollSubject(ServerRequestInterface $request, int $id, int $student_id): ResponseInterface
    {
        return $this->run(function () use ($request, $id, $student_id) {
            $cycleId = $request->getQueryParams()['cycle_id'] ?? null;
            if (filter_var($cycleId, FILTER_VALIDATE_INT) === false || (int) $cycleId < 1) {
                throw new \InvalidArgumentException('Invalid cycle_id.');
            }
            $this->service->unenrollSubject($this->user($request), $id, $student_id, (int) $cycleId);
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
        } catch (EnrollmentException $exception) {
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
