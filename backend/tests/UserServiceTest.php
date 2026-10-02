<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Auth\Service\PasswordHasher;
use App\Shared\TransactionRunner;
use App\Users\Repository\UserRepository;
use App\Users\Service\UserException;
use App\Users\Service\UserService;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

final class UserServiceTest extends TestCase
{
    public function testCrossSchoolUserIsHiddenAsNotFound(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('find')->with(12, 'teacher', 88)->willReturn(null);
        $service = new UserService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new PasswordHasher(),
        );

        try {
            $service->find(new CurrentUser(1, 'admin', 12), 'teacher', 88);
            self::fail('Expected cross-school user to be hidden.');
        } catch (UserException $exception) {
            self::assertSame(404, $exception->status);
        }
    }

    public function testNonAdminCannotResetPassword(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('updatePassword');
        $service = new UserService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new PasswordHasher(),
        );

        try {
            $service->resetPassword(new CurrentUser(3, 'teacher', 12), 88, 'valid-password');
            self::fail('Expected non-admin reset to be rejected.');
        } catch (UserException $exception) {
            self::assertSame(403, $exception->status);
        }
    }

    public function testStudentEnrollmentNumberConflictIsSchoolScoped(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('emailExists')->with('ada@example.com')->willReturn(false);
        $repository->expects(self::once())->method('enrollmentExists')->with(12, 'A-1')->willReturn(true);
        $service = new UserService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new PasswordHasher(),
        );

        try {
            $service->create(new CurrentUser(1, 'admin', 12), 'student', [
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
                'password' => 'valid-password', 'enrollment_number' => 'A-1', 'birth_date' => '2010-01-02',
            ]);
            self::fail('Expected duplicate enrollment number rejection.');
        } catch (UserException $exception) {
            self::assertSame(400, $exception->status);
        }
    }
}
