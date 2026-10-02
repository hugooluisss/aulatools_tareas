<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\CurrentUser;
use App\Auth\Repository\AuthRepository;
use App\Shared\TransactionRunner;
use DomainException;
use Yiisoft\Db\Exception\IntegrityException;

final class AuthService
{
    public function __construct(
        private AuthRepository $repository,
        private TransactionRunner $transactions,
        private PasswordHasherInterface $passwordHasher,
        private JwtService $jwtService,
    ) {
    }

    public function registerSchool(array $data): void
    {
        foreach (['school_name', 'first_name', 'last_name', 'email', 'password'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new DomainException('Invalid registration data.');
            }
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['password']) < 8) {
            throw new DomainException('Invalid registration data.');
        }
        try {
            $this->transactions->run(function () use ($data): void {
                if ($this->repository->emailExists($data['email'])) {
                    throw new DomainException('Email already registered.');
                }
                $schoolId = $this->repository->createSchool($data['school_name']);
                $this->repository->createUser(
                    $schoolId,
                    $data['email'],
                    $this->passwordHasher->hash($data['password']),
                    $data['first_name'],
                    $data['last_name'],
                );
            });
        } catch (\Throwable $exception) {
            if ($exception instanceof IntegrityException && $this->isEmailDuplicate($exception)) {
                throw new DomainException('Email already registered.', 0, $exception);
            }
            throw $exception;
        }
    }

    public function login(string $email, string $password): string
    {
        $user = $this->repository->findByEmail($email);
        $passwordHash = $user['password_hash']
            ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $passwordIsValid = $this->passwordHasher->verify($password, $passwordHash);
        if (
            $user === null
            || !$passwordIsValid
            || ($user['role'] === 'student' && $user['student_status'] !== 'active')
        ) {
            throw new DomainException('Invalid email or password.');
        }
        return $this->jwtService->issue((int) $user['id'], $user['role'], (int) $user['school_id']);
    }

    public function changePassword(CurrentUser $user, string $currentPassword, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new DomainException('Invalid password.');
        }
        $record = $this->repository->findPasswordHashById($user->id);
        if ($record === null || !$this->passwordHasher->verify($currentPassword, $record['password_hash'])) {
            throw new DomainException('Current password is incorrect.');
        }
        $this->repository->updatePassword($user->id, $this->passwordHasher->hash($newPassword));
    }

    private function isEmailDuplicate(IntegrityException $exception): bool
    {
        $errorInfo = $exception->errorInfo ?? [];
        $message = strtolower($exception->getMessage());
        return (string) ($errorInfo[1] ?? '') === '1062'
            && str_contains($message, 'uq_users_email');
    }
}
