<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\Repository\PasswordResetRepository;
use App\Auth\Service\PasswordHasherInterface;
use App\Auth\Service\PasswordResetMailer;
use App\Auth\Service\PasswordResetService;
use App\Shared\Mail\EmailMessage;
use App\Shared\Mail\EmailSender;
use App\Shared\TransactionRunner;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class PasswordResetServiceTest extends TestCase
{
    public function testExistingEmailIssuesHashedTokenForExactlyOneHour(): void
    {
        $repository = $this->createMock(PasswordResetRepository::class);
        $repository->method('findUserByEmail')->willReturn(['id' => 7]);
        $repository->expects(self::once())->method('invalidatePending');
        $repository->expects(self::once())->method('create')->with(
            7,
            self::callback(static fn (string $hash): bool => strlen($hash) === 64 && ctype_xdigit($hash)),
            '2026-10-03 13:00:00',
            '2026-10-03 12:00:00',
        );
        $this->service($repository, new InMemoryEmailSender())->request('known@example.com');
    }

    public function testUnknownEmailDoesNotCreateTokenOrSendEmail(): void
    {
        $repository = $this->createMock(PasswordResetRepository::class);
        $repository->method('findUserByEmail')->willReturn(null);
        $repository->expects(self::never())->method('create');
        $repository->expects(self::never())->method('invalidatePending');
        $service = $this->service($repository, new InMemoryEmailSender());
        $service->request('missing@example.com');
    }

    public function testExpiredTokenIsRejected(): void
    {
        $repository = $this->createMock(PasswordResetRepository::class);
        $repository->expects(self::once())->method('findValid')->with(hash('sha256', 'expired'), '2026-10-03 13:00:00')->willReturn(null);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid or expired token.');
        $this->service($repository, new InMemoryEmailSender(), new DateTimeImmutable('2026-10-03 13:00:00', new DateTimeZone('UTC')))->reset('expired', 'valid-password');
    }

    public function testReusedTokenIsRejected(): void
    {
        $repository = $this->createMock(PasswordResetRepository::class);
        $repository->expects(self::once())->method('findValid')->willReturn(null);
        $this->expectException(DomainException::class);
        $this->service($repository, new InMemoryEmailSender())->reset('used', 'valid-password');
    }

    public function testSuccessfulResetUpdatesPasswordAndConsumesToken(): void
    {
        $repository = $this->createMock(PasswordResetRepository::class);
        $repository->expects(self::once())->method('findValid')->with(hash('sha256', 'valid-token'), '2026-10-03 12:00:00')->willReturn(['id' => 3, 'user_id' => 7]);
        $repository->expects(self::once())->method('updatePassword')->with(7, 'hash:next-password');
        $repository->expects(self::once())->method('markUsed')->with(3, '2026-10-03 12:00:00');
        $repository->expects(self::once())->method('invalidatePending')->with(7, '2026-10-03 12:00:00');
        $hasher = new class () implements PasswordHasherInterface {
            public function hash(string $password): string { return 'hash:' . $password; }
            public function verify(string $password, string $hash): bool { return false; }
        };
        $this->service($repository, new InMemoryEmailSender(), null, $hasher)->reset('valid-token', 'next-password');
    }

    public function testWeakPasswordLeavesTokenUntouched(): void
    {
        $repository = $this->createMock(PasswordResetRepository::class);
        $repository->expects(self::never())->method('findValid');
        $repository->expects(self::never())->method('markUsed');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid password.');
        $this->service($repository, new InMemoryEmailSender())->reset('valid-token', 'short');
    }

    public function testEmailContainsRecipientAndResetToken(): void
    {
        $repository = $this->createMock(PasswordResetRepository::class);
        $repository->method('findUserByEmail')->willReturn(['id' => 7]);
        $sender = new InMemoryEmailSender();
        $this->service($repository, $sender)->request('known@example.com');
        self::assertCount(1, $sender->messages);
        self::assertSame('known@example.com', $sender->messages[0]->to);
        self::assertStringContainsString('token=', $sender->messages[0]->textBody);
        self::assertMatchesRegularExpression('/token=[a-f0-9]{64}/', $sender->messages[0]->textBody);
    }

    private function service(PasswordResetRepository $repository, EmailSender $sender, ?DateTimeImmutable $now = null, ?PasswordHasherInterface $hasher = null): PasswordResetService
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $transaction = $this->createMock(TransactionInterface::class);
        $connection->method('beginTransaction')->willReturn($transaction);
        $clock = static fn (): DateTimeImmutable => $now ?? new DateTimeImmutable('2026-10-03 12:00:00', new DateTimeZone('UTC'));
        $hasher ??= new class () implements PasswordHasherInterface {
            public function hash(string $password): string { return password_hash($password, PASSWORD_DEFAULT); }
            public function verify(string $password, string $hash): bool { return password_verify($password, $hash); }
        };
        return new PasswordResetService($repository, new TransactionRunner($connection), $hasher, new PasswordResetMailer($sender), $clock);
    }
}

final class InMemoryEmailSender implements EmailSender
{
    /** @var list<EmailMessage> */
    public array $messages = [];

    public function send(EmailMessage $message): void
    {
        $this->messages[] = $message;
    }
}
