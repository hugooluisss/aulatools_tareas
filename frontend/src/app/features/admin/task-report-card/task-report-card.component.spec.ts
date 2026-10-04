import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { ToastService } from '../../../core/services/toast.service';
import { ReportsService } from '../reports.service';
import { TaskReportCardComponent } from './task-report-card.component';

describe('TaskReportCardComponent', () => {
  it('loads students, supports select all and downloads selected cards', () => {
    const reports = {
      options: () => ({
        subscribe: (next: (options: any) => void) =>
          next({
            groups: [
              { id: 1, name: '2A', subjects: [{ id: 8, code: 'MAT', name: 'Matemáticas' }] },
            ],
          }),
      }),
      students: vi
        .fn()
        .mockReturnValue({
          subscribe: (observer: any) =>
            observer.next({
              items: [{ id: 4, first_name: 'Ana', last_name: 'López', enrollment_number: 'A4' }],
            }),
        }),
      taskReportCard: vi
        .fn()
        .mockReturnValue({ subscribe: (observer: any) => observer.next(new Blob(['pdf'])) }),
    };
    TestBed.configureTestingModule({
      imports: [TaskReportCardComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ReportsService, useValue: reports },
      ],
    });
    const fixture = TestBed.createComponent(TaskReportCardComponent);
    fixture.detectChanges();
    fixture.componentInstance.loadStudents('8');
    fixture.detectChanges();
    expect(reports.students).toHaveBeenCalledWith(8);
    expect(fixture.nativeElement.textContent).toContain('Ana');
    fixture.componentInstance.selectAll(true);
    const click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
    fixture.componentInstance.download();
    expect(reports.taskReportCard).toHaveBeenCalledWith(8, [4]);
    expect(click).toHaveBeenCalled();
  });

  it('requires at least one student and restores the busy state after errors', () => {
    const reports = {
      options: () => ({ subscribe: () => {} }),
      students: () => ({ subscribe: () => {} }),
      taskReportCard: vi
        .fn()
        .mockReturnValue({
          subscribe: (observer: any) => observer.error(new Error('Falló el reporte')),
        }),
    };
    const toast = { show: vi.fn() };
    TestBed.configureTestingModule({
      imports: [TaskReportCardComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ReportsService, useValue: reports },
        { provide: ToastService, useValue: toast },
      ],
    });
    const fixture = TestBed.createComponent(TaskReportCardComponent);
    fixture.detectChanges();
    fixture.componentInstance.subjectId.set('8');
    fixture.componentInstance.download();
    expect(reports.taskReportCard).not.toHaveBeenCalled();
    fixture.componentInstance.selectedStudentIds.set([4]);
    fixture.componentInstance.download();
    expect(toast.show).toHaveBeenCalledWith('Falló el reporte');
    expect(fixture.componentInstance.loading()).toBe(false);
  });
});
