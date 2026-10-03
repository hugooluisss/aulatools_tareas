<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Reports\Repository\AttendanceReportRepository;
use App\Reports\Service\AttendanceReportService;
use PHPUnit\Framework\TestCase;

final class AttendanceReportServiceTest extends TestCase
{
    public function testDatesValidationAndSubjectFilter(): void
    {
        $repository = $this->createMock(AttendanceReportRepository::class);
        $repository->method('group')->willReturn(['id' => 2, 'school_name' => 'School', 'cycle_name' => 'Cycle', 'group_name' => 'A', 'logo_path' => null]);
        $repository->expects(self::once())->method('subject')->with(1, 7)->willReturn(['id' => 7, 'name' => 'Math', 'teacher_first_name' => 'T', 'teacher_last_name' => 'One']);
        $repository->expects(self::once())->method('students')->with(1, 2, 7)->willReturn([]);
        $service = new AttendanceReportService($repository);
        $dates = AttendanceReportService::businessDates(new \DateTimeImmutable('2026-10-09'), 3);
        self::assertSame(['2026-10-09', '2026-10-12', '2026-10-13'], array_map(static fn ($date) => $date->format('Y-m-d'), $dates));
        $dates = AttendanceReportService::businessDates(new \DateTimeImmutable('2026-10-10'), 2);
        self::assertSame(['2026-10-12', '2026-10-13'], array_map(static fn ($date) => $date->format('Y-m-d'), $dates));
        $report = $service->generate(new CurrentUser(1, 'admin', 1), [
            'group_id' => '2', 'subject_id' => '7', 'start_date' => '2026-10-05', 'days' => '5',
        ]);
        self::assertStringStartsWith('%PDF', $report['bytes']);
        foreach ([['days' => '0'], ['days' => '15'], ['start_date' => '2026-02-30']] as $invalid) {
            try {
                $service->generate(new CurrentUser(1, 'admin', 1), array_merge([
                    'group_id' => '2', 'start_date' => '2026-10-05', 'days' => '5',
                ], $invalid));
                self::fail('Expected invalid input rejection.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
}
