<?php

declare(strict_types=1);

namespace App\Users\Service;

use App\Auth\CurrentUser;
use App\Auth\Service\PasswordHasherInterface;
use App\Shared\TransactionRunner;
use App\Shared\PhoneNumber;
use App\Shared\ImageUpload;
use App\Users\Repository\UserRepository;

final class UserService
{
    public function __construct(
        private UserRepository $repository,
        private TransactionRunner $transactions,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function list(CurrentUser $user, string $role, int $page, int $perPage, ?string $status = null): array
    {
        $this->admin($user);
        if (
            $page < 1
            || $perPage < 1
            || $perPage > 100
            || ($status !== null && !in_array($status, ['active', 'inactive'], true))
        ) {
            throw new UserException('Invalid pagination or status.', 400);
        }
        $result = $this->repository->list($user->schoolId, $role, ($page - 1) * $perPage, $perPage, $status);
        $data = $result['data'];
        if ($role === 'student') {
            $data = array_map(function (array $student): array {
                $student['enrollment'] = $student['enrollment_id'] === null ? null : [
                    'id' => (int) $student['enrollment_id'],
                    'cycle_id' => (int) $student['enrollment_cycle_id'],
                    'cycle_name' => $student['enrollment_cycle_name'],
                    'group_id' => (int) $student['enrollment_group_id'],
                    'group_name' => $student['enrollment_group_name'],
                ];
                unset(
                    $student['enrollment_id'],
                    $student['enrollment_cycle_id'],
                    $student['enrollment_cycle_name'],
                    $student['enrollment_group_id'],
                    $student['enrollment_group_name'],
                );
                return $this->formatPhoto($student);
            }, $data);
        } else {
            $data = array_map($this->formatPhoto(...), $data);
        }
        return \App\Shared\Paginator::build($data, $result['total'], $page, $perPage);
    }

    public function find(CurrentUser $user, string $role, int $id): array
    {
        $this->admin($user);
        $row = $this->repository->find($user->schoolId, $role, $id) ?? throw new UserException('User not found.', 404);
        return $this->formatPhoto($row);
    }

    public function create(CurrentUser $user, string $role, array $data): array
    {
        $this->admin($user);
        $this->validate($role, $data, true);
        unset($data['enrollment_number']);
        if ($this->repository->emailExists($data['email'])) {
            throw new UserException('Email already registered.', 400);
        }
        $id = $this->transactions->run(fn (): int => $this->repository->create(
            $user->schoolId,
            $role,
            $data,
            $this->passwordHasher->hash($data['password']),
        ));
        return $this->find($user, $role, $id);
    }

    public function update(CurrentUser $user, string $role, int $id, array $data): array
    {
        $this->admin($user);
        $existing = $this->find($user, $role, $id);
        if ($role === 'student') {
            $data['status'] ??= $existing['status'];
        }
        $fields = $role === 'student'
            ? ['address', 'contact_phone', 'guardian_name', 'guardian_phone']
            : ['address', 'phone'];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = $existing[$field] ?? null;
            }
        }
        $this->validate($role, $data, false);
        if ($this->repository->emailExists($data['email'], $id)) {
            throw new UserException('Email already registered.', 400);
        }
        $this->repository->update($user->schoolId, $role, $id, $data);
        return $this->find($user, $role, $id);
    }

    public function status(CurrentUser $user, int $id, string $status): array
    {
        $this->admin($user);
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new UserException('Invalid student status.', 400);
        }
        $this->find($user, 'student', $id);
        $this->repository->setStudentStatus($user->schoolId, $id, $status);
        return $this->find($user, 'student', $id);
    }

    public function delete(CurrentUser $user, string $role, int $id): void
    {
        $this->admin($user);
        $this->find($user, $role, $id);
        $photo = $this->repository->delete($user->schoolId, $role, $id);
        if ($photo !== null) {
            $this->deletePhotoFile($photo);
        }
    }

    public function savePhoto(CurrentUser $user, int $id, string $temporaryPath, int $size): array
    {
        $this->admin($user);
        $this->find($user, 'teacher', $id);
        try {
            $path = ImageUpload::store($temporaryPath, $size, dirname(__DIR__, 3) . '/public/uploads/teachers', '/uploads/teachers/', $id . '-');
        } catch (\InvalidArgumentException $exception) {
            throw new UserException($exception->getMessage(), str_contains($exception->getMessage(), '2 MB') ? 413 : 400);
        } catch (\Throwable $exception) {
            throw new UserException('Could not store photo.', 500);
        }
        $oldPath = $this->repository->photoPath($user->schoolId, $id);
        $this->repository->setPhotoPath($user->schoolId, $id, $path);
        if ($oldPath !== null) {
            $this->deletePhotoFile($oldPath);
        }
        return $this->find($user, 'teacher', $id);
    }

    public function removePhoto(CurrentUser $user, int $id): array
    {
        $this->admin($user);
        $this->find($user, 'teacher', $id);
        $photo = $this->repository->photoPath($user->schoolId, $id);
        $this->repository->setPhotoPath($user->schoolId, $id, null);
        if ($photo !== null) {
            $this->deletePhotoFile($photo);
        }
        return $this->find($user, 'teacher', $id);
    }

    public function resetPassword(CurrentUser $user, int $id, string $password): void
    {
        $this->admin($user);
        if (strlen($password) < 8) {
            throw new UserException('Password must be at least 8 characters.', 400);
        }
        if (!$this->repository->updatePassword($user->schoolId, $id, $this->passwordHasher->hash($password))) {
            throw new UserException('User not found.', 404);
        }
    }

    private function validate(string $role, array $data, bool $creating): void
    {
        $fields = ['first_name', 'last_name', 'email'];
        if ($creating) {
            $fields[] = 'password';
        }
        if ($role === 'student') {
            $fields[] = 'birth_date';
        }
        foreach ($fields as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new UserException("Invalid {$field}.", 400);
            }
        }
        if (
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
            || (isset($data['password']) && strlen($data['password']) < 8)
        ) {
            throw new UserException('Invalid email or password.', 400);
        }
        if ($role === 'student') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['birth_date']);
            if ($date === false || $date->format('Y-m-d') !== $data['birth_date']) {
                throw new UserException('Invalid birth date.', 400);
            }
            if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'], true)) {
                throw new UserException('Invalid student status.', 400);
            }
        }
        $fields = $role === 'student'
            ? ['address', 'contact_phone', 'guardian_name', 'guardian_phone']
            : ['address', 'phone'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                if ($data[$field] !== null && !is_string($data[$field])) {
                    throw new UserException("Invalid {$field}.", 400);
                }
                $data[$field] = $this->text($data[$field]);
                if ($data[$field] !== null
                    && mb_strlen($data[$field]) > (str_contains($field, 'phone') ? 20 : ($field === 'guardian_name' ? 180 : 255))
                ) {
                    throw new UserException("Invalid {$field}.", 400);
                }
                if (str_contains($field, 'phone')) {
                    $value = $data[$field];
                    $data[$field] = $value === null ? null : PhoneNumber::normalize($value);
                    if ($value !== null && $data[$field] === null) {
                        throw new UserException('Invalid phone number.', 400);
                    }
                }
            } else {
                $data[$field] = null;
            }
        }
    }

    private function text(?string $value): ?string
    {
        $value = trim($value ?? '');
        return $value === '' ? null : $value;
    }

    private function formatPhoto(array $row): array
    {
        if (($row['role'] ?? null) === 'teacher') {
            $row['photo_url'] = $row['photo_path'] ?? null;
            unset($row['photo_path']);
        }
        return $row;
    }

    private function deletePhotoFile(string $path): void
    {
        if (preg_match('#^/uploads/teachers/[a-zA-Z0-9._-]+$#D', $path)) {
            ImageUpload::delete($path, '/uploads/teachers/', dirname(__DIR__, 3) . '/public');
        }
    }

    private function admin(CurrentUser $user): void
    {
        if ($user->role !== 'admin') {
            throw new UserException('Forbidden.', 403);
        }
    }
}
