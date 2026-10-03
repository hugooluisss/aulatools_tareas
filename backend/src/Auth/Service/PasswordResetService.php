<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Repository\PasswordResetRepository;
use App\Shared\TransactionRunner;
use App\Shared\Mail\MailException;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;

final class PasswordResetService
{
    private \Closure $clock;

    public function __construct(private PasswordResetRepository $repository, private TransactionRunner $transactions, private PasswordHasherInterface $passwordHasher, private PasswordResetMailer $mailer, ?callable $clock = null)
    {
        $this->clock = $clock === null ? static fn (): DateTimeImmutable => new DateTimeImmutable('now', new DateTimeZone('UTC')) : \Closure::fromCallable($clock);
    }

    public function request(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $user = $this->repository->findUserByEmail($email);
        if ($user === null) {
            return;
        }
        $now = ($this->clock)()->setTimezone(new DateTimeZone('UTC'));
        $token = bin2hex(random_bytes(32));
        $this->transactions->run(function () use ($user, $now, $token): void {
            $this->repository->invalidatePending((int) $user['id'], $now->format('Y-m-d H:i:s'));
            $this->repository->create((int) $user['id'], hash('sha256', $token), $now->modify('+1 hour')->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'));
        });
        try {
            $this->mailer->send($email, $token);
        } catch (MailException) {
            // Keep delivery failures private; do not log token or recipient details.
        }
    }

    public function reset(string $token, string $password): void
    {
        if (strlen($password) < 8) {
            throw new DomainException('Invalid password.');
        }
        $now = ($this->clock)()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $this->transactions->run(function () use ($token, $password, $now): void {
            $record = $this->repository->findValid(hash('sha256', $token), $now);
            if ($record === null) {
                throw new DomainException('Invalid or expired token.');
            }
            $this->repository->updatePassword((int) $record['user_id'], $this->passwordHasher->hash($password));
            $this->repository->markUsed((int) $record['id'], $now);
            $this->repository->invalidatePending((int) $record['user_id'], $now);
        });
    }
}
