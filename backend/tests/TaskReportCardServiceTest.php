<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\CurrentUser;
use App\Reports\Model\RenderedReport;
use App\Reports\Model\ReportViewModel;
use App\Reports\Renderer\ReportRenderer;
use App\Reports\Repository\ReportRepository;
use App\Reports\Service\TaskReportCardService;
use PHPUnit\Framework\TestCase;

final class TaskReportCardServiceTest extends TestCase
{
    public function testBuildsReportForEnrolledStudentsWithDateOnlyValues(): void
    {
        $repository = $this->createMock(ReportRepository::class);
        $repository->method('taskSubject')->willReturn([
            'id' => 7,
            'subject_name' => 'Matemáticas',
            'teacher_id' => 5,
            'teacher_first_name' => 'Ana',
            'teacher_last_name' => 'Maestra',
            'school_name' => 'Escuela',
        ]);
        $repository->method('enrolledStudents')->willReturn([[
            'id' => 11,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'enrollment_number' => 'M-11',
            'cycle_id' => 3,
            'cycle_name' => '2026',
        ]]);
        $repository->method('tasks')->willReturn([[
            'task_name' => 'Ensayo',
            'due_date' => '2026-10-01',
            'cycle_id' => 3,
            'student_id' => 11,
            'delivered_date' => '2026-09-30',
            'grade' => '95.00',
        ], [
            'task_name' => 'Sin entrega',
            'due_date' => '2026-10-05',
            'cycle_id' => 3,
            'student_id' => null,
            'delivered_date' => null,
            'grade' => null,
        ]]);
        $renderer = new class () implements ReportRenderer {
            public ?ReportViewModel $viewModel = null;

            public function render(ReportViewModel $viewModel): RenderedReport
            {
                $this->viewModel = $viewModel;
                return new RenderedReport('%PDF-fake', 'application/pdf', $viewModel->header['filename']);
            }
        };
        $service = new TaskReportCardService($repository, $renderer);
        $report = $service->generate(new CurrentUser(1, 'admin', 1), ['subject_id' => 7, 'student_ids' => [11]]);
        self::assertSame('%PDF-fake', $report['bytes']);
        self::assertSame('2026-09-30', $renderer->viewModel->sections[0]['table']['rows'][0][2]);
        self::assertSame('—', $renderer->viewModel->sections[0]['table']['rows'][1][2]);
        self::assertSame('—', $renderer->viewModel->sections[0]['table']['rows'][1][3]);
    }

    public function testRejectsDuplicateStudentIds(): void
    {
        $service = new TaskReportCardService($this->createMock(ReportRepository::class), $this->createMock(ReportRenderer::class));
        $this->expectException(\InvalidArgumentException::class);
        $service->generate(new CurrentUser(1, 'admin', 1), ['subject_id' => 7, 'student_ids' => [11, 11]]);
    }
}
