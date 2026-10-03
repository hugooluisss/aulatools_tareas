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
        ], $result['items'][0]['enrollment']);
        self::assertNull($result['items'][1]['enrollment']);
        self::assertArrayNotHasKey('enrollment_id', $result['items'][0]);
    }

    public function testPhotoRejectsInvalidTypeAndOversizeThenReplacesOldPhoto(): void
    {
        $oldFile = null;
        $newFile = null;
        $repository = $this->createMock(UserRepository::class);
        $row = [
            'id' => 12, 'school_id' => 88, 'role' => 'teacher', 'email' => 'test@example.com',
            'first_name' => 'Test', 'last_name' => 'Teacher',
            'photo_path' => null,
        ];
        $repository->method('find')->willReturnCallback(static function () use (&$row): array {
            return $row;
        });
        $repository->method('photoPath')->willReturn('/uploads/teachers/old-test-photo.png');
        $repository->method('setPhotoPath')->willReturnCallback(static function (int $schoolId, int $id, ?string $path) use (&$row): void {
            $row['photo_path'] = $path;
        });
        $service = new UserService(
            $repository,
            new TransactionRunner($this->createMock(ConnectionInterface::class)),
            new PasswordHasher(),
        );
        $user = new CurrentUser(1, 'admin', 88);
        $invalid = tempnam(sys_get_temp_dir(), 'photo-invalid-');
        file_put_contents($invalid, 'not an image');
        try {
            $service->savePhoto($user, 12, $invalid, filesize($invalid));
            self::fail('Expected invalid photo type to be rejected.');
        } catch (UserException $exception) {
            self::assertSame(400, $exception->status);
        } finally {
            unlink($invalid);
        }
        try {
            $service->savePhoto($user, 12, __FILE__, 2 * 1024 * 1024 + 1);
            self::fail('Expected oversized photo to be rejected.');
        } catch (UserException $exception) {
            self::assertSame(413, $exception->status);
        }

        $directory = dirname(__DIR__) . '/public/uploads/teachers';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $oldFile = $directory . '/old-test-photo.png';
        $newFile = null;
        file_put_contents($oldFile, 'old photo');
        $repository->expects(self::once())->method('setPhotoPath')->with(88, 12, self::callback(static fn (?string $path): bool => is_string($path) && preg_match('#^/uploads/teachers/12-[a-f0-9]{16}\.png$#', $path) === 1));
        $png = tempnam(sys_get_temp_dir(), 'photo-png-');
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX5sAAAAASUVORK5CYII=', true));
        try {
            $result = $service->savePhoto($user, 12, $png, filesize($png));
            $newFile = dirname(__DIR__) . '/public' . $result['photo_url'];
            self::assertMatchesRegularExpression('#^/uploads/teachers/12-[a-f0-9]{16}\.png$#', $result['photo_url']);
            self::assertFileDoesNotExist($oldFile);
            self::assertFileExists($newFile);
        } finally {
            unlink($png);
            if ($newFile !== null) {
                @unlink($newFile);
            }
            if ($oldFile !== null) {
                @unlink($oldFile);
            }
        }
    }
}
