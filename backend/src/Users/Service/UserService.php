<?php

declare(strict_types=1);

namespace App\Users\Service;

use App\Auth\CurrentUser;
use App\Auth\Service\PasswordHasherInterface;
use App\Shared\TransactionRunner;
use App\Users\Repository\UserRepository;
use DomainException;

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
            $data = array_map(static function (array $student): array {
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
                return $student;
            }, $data);
        }
        return [
            'data' => $data,
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ];
    }

    public function find(CurrentUser $user, string $role, int $id): array
    {
        $this->admin($user);
        return $this->repository->find($user->schoolId, $role, $id) ?? throw new UserException('User not found.', 404);
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
        $this->repository->delete($user->schoolId, $role, $id);
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
    }

    private function admin(CurrentUser $user): void
    {
        if ($user->role !== 'admin') {
            throw new UserException('Forbidden.', 403);
        }
    }
}
