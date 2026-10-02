<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Subjects\Repository\SubjectRepository;
use App\Subjects\Service\SubjectService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class SubjectServiceTest extends TestCase
{
    public function testTeacherOnlySeesAssignedSubjectAndStudentOnlySeesEnrolledSubject(): void
    {
        $repository = new FakeSubjectRepository();
        $service = new SubjectService($repository);
        self::assertSame(10, $service->view(new CurrentUser(4, 'teacher', 2), 7)['id']);
        self::assertSame(10, $service->view(new CurrentUser(8, 'student', 2), 7)['id']);
        $this->expectException(DomainException::class);
        $service->view(new CurrentUser(5, 'teacher', 2), 7);
    }

    public function testSubjectCreationRequiresActiveCycleAndSameSchoolTeacher(): void
    {
        $repository = new FakeSubjectRepository();
        $service = new SubjectService($repository);
        $this->expectException(DomainException::class);
        $service->create(new CurrentUser(1, 'admin', 2), ['cycle_id' => 99, 'teacher_id' => 4, 'name' => 'Math']);
    }
}

final class FakeSubjectRepository extends SubjectRepository
{
    public function __construct()
    {
    }

    public function find(int $schoolId, int $subjectId): ?array
    {
        return $schoolId === 2 && $subjectId === 7
            ? ['id' => 10, 'cycle_id' => 1, 'teacher_id' => 4, 'name' => 'Math', 'status' => 'in_progress']
            : null;
    }

    public function isEnrolled(int $subjectId, int $studentId): bool
    {
        return $studentId === 8;
    }

    public function cycleIsActive(int $schoolId, int $cycleId): bool
    {
        return $schoolId === 2 && $cycleId === 1;
    }

    public function teacherBelongsToSchool(int $schoolId, int $teacherId): bool
    {
        return $schoolId === 2 && $teacherId === 4;
    }

    public function create(int $cycleId, int $teacherId, string $name): int
    {
        return 7;
    }
}
