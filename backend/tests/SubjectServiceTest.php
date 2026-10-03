<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Subjects\Repository\SubjectRepository;
use App\Subjects\Service\SubjectException;
use App\Subjects\Service\SubjectService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class SubjectServiceTest extends TestCase
{
    public function testDuplicateCodeReturnsConflict(): void
    {
        $repository = new FakeSubjectRepository();
        $repository->duplicate = true;
        try {
            (new SubjectService($repository))->create(new CurrentUser(1, 'admin', 2), ['code' => 'MAT1', 'name' => 'Math', 'teacher_id' => 4]);
            self::fail('Expected duplicate code conflict.');
        } catch (SubjectException $exception) {
            self::assertSame(409, $exception->status);
        }
    }

    public function testInactivePlanReturns422(): void
    {
        $repository = new FakeSubjectRepository();
        $repository->planActive = false;
        try {
            (new SubjectService($repository))->create(new CurrentUser(1, 'admin', 2), ['code' => 'MAT1', 'name' => 'Math', 'teacher_id' => 4, 'plan_id' => 3]);
            self::fail('Expected inactive plan validation.');
        } catch (SubjectException $exception) {
            self::assertSame(422, $exception->status);
        }
    }

    public function testNullAndEmptyPlanAreOptionalAndNullClearsOnUpdate(): void
    {
        $repository = new FakeSubjectRepository();
        $service = new SubjectService($repository);
        $user = new CurrentUser(1, 'admin', 2);

        $service->create($user, ['code' => 'MAT1', 'name' => 'Math', 'teacher_id' => 4, 'plan_id' => null]);
        $service->create($user, ['code' => 'MAT2', 'name' => 'Math', 'teacher_id' => 4, 'plan_id' => '']);
        $service->update($user, 7, ['code' => 'MAT1', 'name' => 'Math', 'teacher_id' => 4, 'status' => 'active', 'plan_id' => null]);

        self::assertSame([null, null, null], $repository->writtenPlanIds);
    }

    public function testTeacherCanOnlyViewAssignedSubject(): void
    {
        $service = new SubjectService(new FakeSubjectRepository());
        self::assertSame(10, $service->view(new CurrentUser(4, 'teacher', 2), 7)['id']);
        $this->expectException(DomainException::class);
        $service->view(new CurrentUser(5, 'teacher', 2), 7);
    }
}

final class FakeSubjectRepository extends SubjectRepository
{
    public bool $duplicate = false;
    public bool $planActive = true;
    public array $writtenPlanIds = [];

    public function __construct()
    {
    }

    public function list(int $schoolId, string $role, int $userId, array $filters, int $offset, int $limit): array
    {
        return ['data' => [], 'total' => 0];
    }

    public function find(int $schoolId, int $subjectId): ?array
    {
        return $subjectId === 7 ? ['id' => 10, 'teacher_id' => 4, 'name' => 'Math', 'status' => 'active'] : null;
    }

    public function create(int $schoolId, string $code, int $teacherId, string $name, ?int $planId): int
    {
        $this->writtenPlanIds[] = $planId;
        if ($this->duplicate) {
            throw new \Yiisoft\Db\Exception\IntegrityException('duplicate');
        }
        return 7;
    }

    public function update(int $schoolId, int $id, string $code, int $teacherId, string $name, string $status, ?int $planId): void
    {
        $this->writtenPlanIds[] = $planId;
    }

    public function codeExists(int $schoolId, string $code, ?int $exceptId = null): bool
    {
        return $this->duplicate;
    }

    public function activePlanBelongsToSchool(int $schoolId, int $planId): bool
    {
        return $this->planActive;
    }

    public function teacherBelongsToSchool(int $schoolId, int $teacherId): bool
    {
        return $teacherId === 4;
    }

    public function isEnrolled(int $subjectId, int $studentId): bool
    {
        return false;
    }
}
