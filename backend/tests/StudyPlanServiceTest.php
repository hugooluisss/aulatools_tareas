<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\StudyPlans\Repository\StudyPlanRepository;
use App\StudyPlans\Service\StudyPlanException;
use App\StudyPlans\Service\StudyPlanService;
use PHPUnit\Framework\TestCase;

final class StudyPlanServiceTest extends TestCase
{
    public function testDeletingPlanWithSubjectsConflicts(): void
    {
        $repository = new FakeStudyPlanRepository();
        try {
            (new StudyPlanService($repository))->delete(new CurrentUser(56, 'admin', 13), 3);
            self::fail('Expected plan with subjects to be retained.');
        } catch (StudyPlanException $exception) {
            self::assertSame(409, $exception->status);
        }
        self::assertFalse($repository->deleted);
    }

    public function testDuplicateCodeConflicts(): void
    {
        $repository = new FakeStudyPlanRepository();
        $repository->duplicate = true;
        try {
            (new StudyPlanService($repository))->create(new CurrentUser(56, 'admin', 13), ['code' => 'PLAN1', 'name' => 'Primaria']);
            self::fail('Expected duplicate code conflict.');
        } catch (StudyPlanException $exception) {
            self::assertSame(409, $exception->status);
        }
    }
}

final class FakeStudyPlanRepository extends StudyPlanRepository
{
    public bool $duplicate = false;
    public bool $deleted = false;

    public function __construct()
    {
    }

    public function find(int $schoolId, int $id): ?array
    {
        return ['id' => $id, 'subjects_count' => 1];
    }

    public function create(int $schoolId, string $code, string $name): int
    {
        if ($this->duplicate) {
            throw new \Yiisoft\Db\Exception\IntegrityException('duplicate');
        }
        return 3;
    }

    public function codeExists(int $schoolId, string $code, ?int $exceptId = null): bool
    {
        return $this->duplicate;
    }

    public function delete(int $schoolId, int $id): void
    {
        $this->deleted = true;
    }
}
