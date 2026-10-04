<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Reports\Model\RenderedReport;
use App\Reports\Model\ReportViewModel;
use App\Reports\Renderer\PdfReportRenderer;
use App\Reports\Renderer\ReportRenderer;
use App\Reports\Repository\ReportRepository;
use App\Reports\Service\AttendanceReportForbiddenException;
use App\Reports\Service\AttendanceReportService;
use PHPUnit\Framework\TestCase;

final class AttendanceReportServiceTest extends TestCase
{
    public function testDatesValidationAndSubjectFilter(): void
    {
        $repository = $this->createMock(ReportRepository::class);
        $repository->method('group')->willReturn(['id' => 2, 'school_name' => 'School', 'cycle_name' => 'Cycle', 'group_name' => 'A', 'logo_path' => null]);
        $repository->method('groupHasSubject')->willReturn(true);
        $repository->expects(self::once())->method('subject')->with(1, 7)->willReturn(['id' => 7, 'name' => 'Math', 'teacher_first_name' => 'T', 'teacher_last_name' => 'One']);
        $repository->expects(self::once())->method('students')->with(1, 2, 7)->willReturn([]);
        $renderer = new PdfReportRenderer();
        $service = new AttendanceReportService($repository, $renderer);
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

    public function testTeacherCannotRequestUnassignedScope(): void
    {
        $repository = $this->createMock(ReportRepository::class);
        $repository->method('group')->willReturn(['id' => 2, 'school_name' => 'School', 'cycle_name' => 'Cycle', 'group_name' => 'A', 'logo_path' => null]);
        $repository->method('subject')->willReturn(['id' => 7, 'name' => 'Math']);
        $repository->method('groupHasSubject')->willReturn(true);
        $repository->method('teacherOwnsGroupSubject')->willReturn(false);
        $service = new AttendanceReportService($repository, new PdfReportRenderer());
        $this->expectException(AttendanceReportForbiddenException::class);
        $service->generate(new CurrentUser(9, 'teacher', 1), [
            'group_id' => '2', 'subject_id' => '7', 'start_date' => '2026-10-05', 'days' => '5',
        ]);
    }

    public function testFakeRendererConsumesAttendanceViewModel(): void
    {
        $repository = $this->createMock(ReportRepository::class);
        $repository->method('group')->willReturn(['id' => 2, 'school_name' => 'School', 'cycle_name' => 'Cycle', 'group_name' => 'A', 'logo_path' => null]);
        $repository->method('groupHasSubject')->willReturn(true);
        $repository->method('students')->willReturn([['first_name' => 'Ada', 'last_name' => 'Lovelace', 'enrollment_number' => 'A1']]);
        $renderer = new class () implements ReportRenderer {
            public ?ReportViewModel $viewModel = null;

            public function render(ReportViewModel $viewModel): RenderedReport
            {
                $this->viewModel = $viewModel;
                return new RenderedReport('rendered', 'text/plain', $viewModel->header['filename']);
            }
        };
        $service = new AttendanceReportService($repository, $renderer);
        $service->generate(new CurrentUser(1, 'admin', 1), [
            'group_id' => '2', 'start_date' => '2026-10-05', 'days' => '1',
        ]);
        self::assertSame('Lista de asistencia', $renderer->viewModel->title);
        self::assertSame('Lovelace, Ada', $renderer->viewModel->tables[0]['rows'][0][1]);
    }
}
