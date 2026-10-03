<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Auth\Service\PasswordHasher;
use App\Auth\Service\PasswordHasherInterface;
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

    public function testStudentCreateIgnoresEnrollmentNumberAndUsesInsertedId(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('emailExists')->with('ada@example.com')->willReturn(false);
        $repository->expects(self::never())->method('enrollmentExists');
        $repository->expects(self::once())->method('create')
            ->with(12, 'student', self::callback(static fn (array $data): bool => !isset($data['enrollment_number'])), self::isType('string'))
            ->willReturn(88);
        $repository->expects(self::once())->method('find')->with(12, 'student', 88)->willReturn([
            'id' => 88, 'school_id' => 12, 'role' => 'student', 'email' => 'ada@example.com',
            'first_name' => 'Ada', 'last_name' => 'Lovelace', 'enrollment_number' => '88',
            'birth_date' => '2010-01-02', 'status' => 'active',
        ]);
        $connection = $this->createMock(ConnectionInterface::class);
        $transaction = $this->createMock(\Yiisoft\Db\Transaction\TransactionInterface::class);
        $transaction->expects(self::once())->method('commit');
        $connection->expects(self::once())->method('beginTransaction')->willReturn($transaction);
        $transactions = new TransactionRunner($connection);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn('hash');
        $service = new UserService($repository, $transactions, $hasher);

        $student = $service->create(new CurrentUser(1, 'admin', 12), 'student', [
            'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
            'password' => 'valid-password', 'enrollment_number' => 'client-value', 'birth_date' => '2010-01-02',
        ]);

        self::assertSame('88', $student['enrollment_number']);
    }

    public function testStudentListShapesActiveEnrollment(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('list')->with(12, 'student', 0, 20, null)->willReturn([
            'data' => [[
                'id' => 88, 'enrollment_id' => '5', 'enrollment_cycle_id' => '14',
                'enrollment_cycle_name' => '2026', 'enrollment_group_id' => '11', 'enrollment_group_name' => '1A',
            ], ['id' => 89, 'enrollment_id' => null, 'enrollment_cycle_id' => null,
                'enrollment_cycle_name' => null, 'enrollment_group_id' => null, 'enrollment_group_name' => null]],
            'total' => 2,
        ]);
        $service = new UserService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new PasswordHasher(),
        );

        $result = $service->list(new CurrentUser(1, 'admin', 12), 'student', 1, 20);

        self::assertSame([
            'id' => 5, 'cycle_id' => 14, 'cycle_name' => '2026', 'group_id' => 11, 'group_name' => '1A',
        ], $result['data'][0]['enrollment']);
        self::assertNull($result['data'][1]['enrollment']);
        self::assertArrayNotHasKey('enrollment_id', $result['data'][0]);
    }
}
