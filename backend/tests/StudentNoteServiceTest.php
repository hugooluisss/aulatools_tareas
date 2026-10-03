<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\StudentNotes\Repository\StudentNoteRepository;
use App\StudentNotes\Service\StudentNoteException;
use App\StudentNotes\Service\StudentNoteService;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

final class StudentNoteServiceTest extends TestCase
{
    public function testScenarios(): void
    {
        $repository = new class ($this->createMock(ConnectionInterface::class)) extends StudentNoteRepository {
            public function student(int $schoolId, int $studentId): ?array
            {
                return $schoolId === 2 && $studentId === 7 ? ['user_id' => 7] : null;
            }

            public function teacherHasStudent(int $teacherId, int $studentId): bool
            {
                return $teacherId === 8 && $studentId === 7;
            }

            public function list(int $studentId, int $offset, int $limit): array
            {
                return ['data' => [], 'total' => 0];
            }

            public function create(int $studentId, int $authorId, string $body): int
            {
                if ($studentId !== 7 || $authorId !== 8 || $body !== 'Note') {
                    throw new \LogicException('Unexpected note create arguments.');
                }
                return 10;
            }

            public function find(int $id): ?array
            {
                return [
                    'id' => $id, 'student_id' => 7, 'body' => 'Note', 'created_at' => '2026-10-01 10:00:00',
                    'author_id' => 8, 'first_name' => 'Taylor', 'last_name' => 'Teacher', 'role' => 'teacher',
                ];
            }
        };
        $service = new StudentNoteService($repository);
        $teacher = new CurrentUser(8, 'teacher', 2);
        self::assertSame('teacher', $service->create($teacher, 7, ['body' => ' Note '])['author']['role']);
        self::assertSame(['data' => [], 'meta' => ['page' => 1, 'per_page' => 20, 'total' => 0]], $service->list($teacher, 7, 1, 20));

        foreach ([
            [new CurrentUser(9, 'teacher', 2), 7, ['body' => 'Ok'], 403],
            [new CurrentUser(7, 'student', 2), 7, ['body' => 'Ok'], 404],
            [new CurrentUser(8, 'teacher', 2), 999, ['body' => 'Ok'], 404],
            [$teacher, 7, ['body' => '   '], 400],
            [$teacher, 7, ['body' => str_repeat('x', 2001)], 400],
        ] as [$user, $studentId, $data, $status]) {
            try {
                $service->create($user, $studentId, $data);
                self::fail('Invalid student note scenario was accepted.');
            } catch (StudentNoteException $exception) {
                self::assertSame($status, $exception->status);
            }
        }
    }
}
