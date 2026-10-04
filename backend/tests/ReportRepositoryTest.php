<?php

declare(strict_types=1);

namespace App\Tests;

use App\Reports\Repository\ReportRepository;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class ReportRepositoryTest extends TestCase
{
    public function testOptionsQueryDeduplicatesGroupSubjects(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('queryAll')->willReturn([
            ['group_id' => '11', 'group_name' => 'Grupo 1A', 'subject_id' => '40', 'code' => 'ESP-101', 'subject_name' => 'Español'],
        ]);
        $database = $this->createMock(ConnectionInterface::class);
        $database->expects(self::once())->method('createCommand')->with(
            self::stringContains('SELECT DISTINCT'),
            [':group_school_id' => 13, ':subject_school_id' => 13, ':teacher_id' => 63],
        )->willReturn($command);

        $options = (new ReportRepository($database))->options(13, 'teacher', 63);

        self::assertSame([
            'groups' => [[
                'id' => 11,
                'name' => 'Grupo 1A',
                'subjects' => [['id' => 40, 'code' => 'ESP-101', 'name' => 'Español']],
            ]],
        ], $options);
    }

    public function testEnrolledStudentsBindsEveryExpandedStudentPlaceholder(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('queryAll')->willReturn([]);
        $database = $this->createMock(ConnectionInterface::class);
        $database->expects(self::once())->method('createCommand')->with(
            self::callback(static fn (string $sql): bool => str_contains($sql, 'users.id IN (:student0, :student1)')),
            [':student0' => 60, ':student1' => 61, ':subject_id' => 40, ':school_id' => 13],
        )->willReturn($command);

        self::assertSame([], (new ReportRepository($database))->enrolledStudents(13, 40, [60, 61]));
    }

    public function testTaskQueryBindsDeliveryPlaceholdersForSelectedStudents(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('queryAll')->willReturn([]);
        $database = $this->createMock(ConnectionInterface::class);
        $database->expects(self::once())->method('createCommand')->with(
            self::callback(static fn (string $sql): bool => str_contains($sql, 'task_deliveries.student_id IN (:student0, :student1)')),
            [':student0' => 60, ':student1' => 61, ':subject_id' => 40],
        )->willReturn($command);

        self::assertSame([], (new ReportRepository($database))->tasks(40, [60, 61]));
    }
}
