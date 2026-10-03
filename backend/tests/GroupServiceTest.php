<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Groups\Repository\GroupRepository;
use App\Groups\Service\GroupException;
use App\Groups\Service\GroupService;
use App\Enrollments\Service\TaskDeliveryEnrollmentHook;
use App\Shared\TransactionRunner;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class GroupServiceTest extends TestCase
{
    public function testCycleMustBelongToSchool(): void
    {
        $repository = $this->repository([0]);
        $service = $this->service($repository);
        $this->expectException(\InvalidArgumentException::class);
        $service->create(new CurrentUser(56, 'admin', 13), ['name' => 'A', 'cycle_id' => 7, 'subject_ids' => []]);
    }

    public function testInactiveSubjectIsRejected(): void
    {
        $repository = $this->repository([1, 0]);
        $service = $this->service($repository);
        try {
            $service->create(new CurrentUser(56, 'admin', 13), ['name' => 'A', 'cycle_id' => 7, 'subject_ids' => [9]]);
            self::fail('Expected inactive subject rejection.');
        } catch (GroupException $exception) {
            self::assertSame(400, $exception->status);
        }
    }

    public function testSubjectReplacementKeepsRetiredBindingsAndBatchAddsNewSubjects(): void
    {
        $calls = [];
        $db = $this->createMock(ConnectionInterface::class);
        $old = $this->createMock(CommandInterface::class);
        $old->method('queryAll')->willReturn([['subject_id' => '8'], ['subject_id' => '9']]);
        $deleteGroup = $this->createMock(CommandInterface::class);
        $deleteGroup->method('execute');
        $insert = $this->createMock(CommandInterface::class);
        $insert->method('execute');
        $group = $this->createMock(CommandInterface::class);
        $group->method('queryOne')->willReturn(['id' => '11', 'cycle_id' => '14', 'school_id' => '13']);
        $db->method('createCommand')->willReturnCallback(function (string $sql) use (&$calls, $old, $deleteGroup, $insert, $group) {
            $calls[] = $sql;
            return match (true) {
                str_starts_with($sql, 'SELECT subject_id') => $old,
                str_starts_with($sql, 'DELETE FROM group_subjects') => $deleteGroup,
                str_starts_with($sql, 'INSERT INTO group_subjects') => $insert,
                str_contains($sql, 'INSERT IGNORE INTO enrollment_subject_bindings') => $insert,
                str_starts_with($sql, 'SELECT `groups`.id') => $group,
                default => throw new \RuntimeException('Unexpected query: ' . $sql),
            };
        });

        $hook = $this->createMock(TaskDeliveryEnrollmentHook::class);
        $hook->expects(self::once())->method('onGroupSubjectsAdded')->with(11, 14, [10]);
        (new GroupRepository($db))->replaceSubjects(11, [8, 10], 14, $hook);
        self::assertCount(0, array_filter($calls, static fn (string $sql): bool => str_starts_with($sql, 'DELETE FROM enrollments')));
        self::assertCount(1, array_filter($calls, static fn (string $sql): bool => str_contains($sql, 'INSERT IGNORE INTO enrollment_subject_bindings')));
        self::assertStringContainsString('SELECT enrollments.id, group_subjects.subject_id', end($calls));
    }

    private function service(GroupRepository $repository): GroupService
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->method('commit');
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('beginTransaction')->willReturn($transaction);
        return new GroupService($repository, new TransactionRunner($db), $this->createMock(TaskDeliveryEnrollmentHook::class));
    }

    private function repository(array $counts): GroupRepository
    {
        $commands = [];
        foreach ($counts as $count) {
            $command = $this->createMock(CommandInterface::class);
            $command->method('queryScalar')->willReturn($count);
            $commands[] = $command;
        }
        $db = $this->createMock(ConnectionInterface::class);
        $db->method('createCommand')->willReturnOnConsecutiveCalls(...$commands);
        return new GroupRepository($db);
    }
}
