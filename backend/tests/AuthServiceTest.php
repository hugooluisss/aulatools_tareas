<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Auth\Repository\AuthRepository;
use App\Auth\Service\AuthService;
use App\Auth\Service\JwtService;
use App\Auth\Service\PasswordHasher;
use App\Auth\Service\PasswordHasherInterface;
use App\Shared\TransactionRunner;
use DomainException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Transaction\TransactionInterface;

final class AuthServiceTest extends TestCase
{
    public function testPasswordHashAndJwtClaims(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('valid-password');
        self::assertNotSame('valid-password', $hash);
        self::assertTrue($hasher->verify('valid-password', $hash));
        self::assertFalse($hasher->verify('wrong-password', $hash));

        putenv('JWT_SECRET=01234567890123456789012345678901');
        $jwt = new JwtService();
        $claims = $jwt->decode($jwt->issue(9, 'teacher', 14));
        self::assertSame('9', $claims->sub);
        self::assertSame('teacher', $claims->role);
        self::assertSame(14, $claims->school_id);
        self::assertSame(28800, $claims->exp - $claims->iat);
    }

    public function testDuplicateEmailDoesNotCreateSchool(): void
    {
        $repository = $this->createMock(AuthRepository::class);
        $repository->expects(self::once())->method('emailExists')->with('used@example.com')->willReturn(true);
        $repository->expects(self::never())->method('createSchool');
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('rollBack');
        $service = new AuthService($repository, $this->transactions($transaction), new PasswordHasher(), $this->jwt());

        $this->expectException(DomainException::class);
        $service->registerSchool($this->registration('used@example.com'));
    }

    public function testRegistrationRollsBackWhenUserCreationFails(): void
    {
        $repository = $this->createMock(AuthRepository::class);
        $repository->method('emailExists')->willReturn(false);
        $repository->expects(self::once())->method('createSchool')->willReturn(55);
        $repository->expects(self::once())
            ->method('createUser')
            ->willThrowException(new \RuntimeException('insert failed'));
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('rollBack');
        $transaction->expects(self::never())->method('commit');
        $service = new AuthService($repository, $this->transactions($transaction), new PasswordHasher(), $this->jwt());

        try {
            $service->registerSchool($this->registration('new@example.com'));
            self::fail('Expected registration failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('insert failed', $exception->getMessage());
        }
    }

    public function testDuplicateKeyRaceMapsToEmailDomainException(): void
    {
        $repository = $this->createMock(AuthRepository::class);
        $repository->method('emailExists')->willReturn(false);
        $repository->method('createSchool')->willReturn(55);
        $repository->method('createUser')->willThrowException(
            new IntegrityException('Duplicate entry for key uq_users_email', ['23000', 1062]),
        );
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(self::once())->method('rollBack');
        $service = new AuthService($repository, $this->transactions($transaction), new PasswordHasher(), $this->jwt());

        try {
            $service->registerSchool($this->registration('raced@example.com'));
            self::fail('Expected duplicate email rejection.');
        } catch (DomainException $exception) {
            self::assertSame('Email already registered.', $exception->getMessage());
        }
    }

    public function testLoginReturnsGenericFailureForWrongCredentialsAndInactiveStudents(): void
    {
        $repository = $this->createMock(AuthRepository::class);
        $repository->method('findByEmail')->willReturnCallback(static function (string $email): ?array {
            if ($email === 'inactive@example.com') {
                return [
                    'id' => 2,
                    'school_id' => 3,
                    'role' => 'student',
                    'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
                    'student_status' => 'inactive',
                ];
            }
            return [
                'id' => 1,
                'school_id' => 3,
                'role' => 'admin',
                'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
                'student_status' => null,
            ];
        });
        $service = new AuthService($repository, $this->transactions(), new PasswordHasher(), $this->jwt());

        $credentials = [
            ['admin@example.com', 'wrong-password'],
            ['inactive@example.com', 'correct-password'],
        ];
        foreach ($credentials as [$email, $password]) {
            try {
                $service->login($email, $password);
                self::fail('Expected login rejection.');
            } catch (DomainException $exception) {
                self::assertSame('Invalid email or password.', $exception->getMessage());
            }
        }
    }

    public function testUnknownEmailStillVerifiesDummyHash(): void
    {
        $repository = $this->createMock(AuthRepository::class);
        $repository->expects(self::once())->method('findByEmail')->willReturn(null);
        $hasher = new class () implements PasswordHasherInterface {
            public int $verifyCalls = 0;

            public function hash(string $password): string
            {
                return $password;
            }

            public function verify(string $password, string $hash): bool
            {
                $this->verifyCalls++;
                return false;
            }
        };
        $service = new AuthService($repository, $this->transactions(), $hasher, $this->jwt());

        try {
            $service->login('missing@example.com', 'attempted-password');
            self::fail('Expected login rejection.');
        } catch (DomainException) {
            self::assertSame(1, $hasher->verifyCalls);
        }
    }

    public function testWrongCurrentPasswordIsRejected(): void
    {
        $repository = $this->createMock(AuthRepository::class);
        $repository->method('findPasswordHashById')->willReturn([
            'password_hash' => password_hash('current-password', PASSWORD_DEFAULT),
        ]);
        $repository->expects(self::never())->method('updatePassword');
        $service = new AuthService($repository, $this->transactions(), new PasswordHasher(), $this->jwt());

        $this->expectException(DomainException::class);
        $service->changePassword(new CurrentUser(1, 'admin', 3), 'wrong-password', 'new-password');
    }

    private function registration(string $email): array
    {
        return [
            'school_name' => 'School',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => $email,
            'password' => 'valid-password',
        ];
    }

    private function jwt(): JwtService
    {
        putenv('JWT_SECRET=01234567890123456789012345678901');
        return new JwtService();
    }

    private function transactions(?TransactionInterface $transaction = null): TransactionRunner
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('beginTransaction')->willReturn(
            $transaction ?? $this->createMock(TransactionInterface::class),
        );
        return new TransactionRunner($connection);
    }
}
