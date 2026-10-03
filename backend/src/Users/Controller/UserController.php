<?php

declare(strict_types=1);

namespace App\Users\Controller;

use App\Auth\CurrentUser;
use App\Users\Service\UserException;
use App\Users\Service\UserService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class UserController
{
    public function __construct(private UserService $service, private ResponseFactoryInterface $responses)
    {
    }

    public function listTeachers(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            'teacher',
            $this->page($request),
            $this->perPage($request),
        ));
    }

    public function listAdmins(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->list($this->user($request), 'admin', $this->page($request), $this->perPage($request)));
    }

    public function createAdmin(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), 'admin', $this->body($request)), 201);
    }

    public function getAdmin(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->find($this->user($request), 'admin', $id));
    }

    public function updateAdmin(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update($this->user($request), 'admin', $id, $this->body($request)));
    }

    public function deleteAdmin(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->delete($request, 'admin', $id);
    }

    public function listStudents(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->list(
            $this->user($request),
            'student',
            $this->page($request),
            $this->perPage($request),
            $request->getQueryParams()['status'] ?? null,
        ));
    }

    public function createTeacher(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), 'teacher', $this->body($request)), 201);
    }

    public function createStudent(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn () => $this->service->create($this->user($request), 'student', $this->body($request)), 201);
    }

    public function getTeacher(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->find($this->user($request), 'teacher', $id));
    }

    public function getStudent(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->find($this->user($request), 'student', $id));
    }

    public function updateTeacher(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update($this->user($request), 'teacher', $id, $this->body($request)));
    }

    public function updateStudent(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->update($this->user($request), 'student', $id, $this->body($request)));
    }

    public function uploadTeacherPhoto(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(function () use ($request, $id): array {
            $photo = $request->getUploadedFiles()['photo'] ?? null;
            if (!$photo instanceof UploadedFileInterface || $photo->getError() !== UPLOAD_ERR_OK || $photo->getSize() === null) {
                throw new UserException('A photo upload is required.', 400);
            }
            $path = tempnam(sys_get_temp_dir(), 'teacher-photo-');
            if ($path === false) {
                throw new UserException('Could not read photo upload.', 400);
            }
            try {
                $photo->moveTo($path);
                return $this->service->savePhoto($this->user($request), $id, $path, (int) $photo->getSize());
            } finally {
                @unlink($path);
            }
        });
    }

    public function deleteTeacherPhoto(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->removePhoto($this->user($request), $id));
    }

    public function studentStatus(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(fn () => $this->service->status(
                $this->user($request),
                $id,
                (string) ($this->body($request)['status'] ?? ''),
            ));
    }

    public function deleteTeacher(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->delete($request, 'teacher', $id);
    }

    public function deleteStudent(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->delete($request, 'student', $id);
    }

    public function resetPassword(ServerRequestInterface $request, int $id): ResponseInterface
    {
        return $this->run(function () use ($request, $id): array {
            $this->service->resetPassword(
                $this->user($request),
                $id,
                (string) ($this->body($request)['new_password'] ?? ''),
            );
            return ['message' => 'Password updated'];
        });
    }

    private function delete(ServerRequestInterface $request, string $role, int $id): ResponseInterface
    {
        try {
            $this->service->delete($this->user($request), $role, $id);
            return $this->responses->createResponse(204);
        } catch (UserException $exception) {
            return $this->error($exception);
        } catch (\Throwable) {
            return \App\Shared\JsonResponse::error($this->responses, 500, 'Unexpected server error.');
        }
    }

    private function run(callable $operation, int $status = 200): ResponseInterface
    {
        try {
            return $this->json($status, $operation());
        } catch (UserException $exception) {
            return $this->error($exception);
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

    private function page(ServerRequestInterface $request): int
    {
        return (int) ($request->getQueryParams()['page'] ?? 1);
    }

    private function perPage(ServerRequestInterface $request): int
    {
        return (int) ($request->getQueryParams()['per_page'] ?? 20);
    }

    private function error(UserException $exception): ResponseInterface
    {
        $status = str_contains($exception->getMessage(), 'already registered') || str_contains($exception->getMessage(), 'already exists')
            ? 409
            : $exception->status;
        $code = $status === 409 ? 'CONFLICT' : null;
        return \App\Shared\JsonResponse::error($this->responses, $status, $exception->getMessage(), $code);
    }

    private function json(int $status, mixed $data): ResponseInterface
    {
        return \App\Shared\JsonResponse::send($this->responses, $status, $data);
    }
}
