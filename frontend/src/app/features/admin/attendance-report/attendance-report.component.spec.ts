import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { ReportsService } from '../reports.service';
import { AttendanceReportComponent } from './attendance-report.component';
import { ToastService } from '../../../core/services/toast.service';

describe('AttendanceReportComponent', () => {
  it('shows group subjects and downloads the selected attendance PDF', async () => {
    const options = {
      options: vi
        .fn()
        .mockReturnValue({
          subscribe: (next: (rows: any) => void) =>
            next({
              groups: [
                { id: 7, name: '2A', subjects: [{ id: 8, code: 'MAT', name: 'Matemáticas' }] },
              ],
            }),
        }),
    };
    const reports = {
      attendance: vi
        .fn()
        .mockReturnValue({ subscribe: (observer: any) => observer.next(new Blob(['pdf'])) }),
    };
    TestBed.configureTestingModule({
      imports: [AttendanceReportComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ReportsService, useValue: { ...options, ...reports } },
      ],
    });
    const fixture = TestBed.createComponent(AttendanceReportComponent);
    fixture.detectChanges();
    fixture.componentInstance.groupId.set('7');
    fixture.componentInstance.subjectId.set('8');
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Matemáticas');
    const click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
    fixture.componentInstance.download();
    expect(reports.attendance).toHaveBeenCalledWith(
      expect.objectContaining({ group_id: 7, subject_id: 8, days: 5 }),
    );
    expect(click).toHaveBeenCalled();
  });

  it('shows API errors through ToastService', () => {
    const report = { options: () => ({ subscribe: () => {} }) };
    const reports = {
      attendance: () => ({
        subscribe: (observer: any) => observer.error(new Error('Error de reporte')),
      }),
    };
    const toast = { show: vi.fn() };
    TestBed.configureTestingModule({
      imports: [AttendanceReportComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ReportsService, useValue: { ...report, ...reports } },
        { provide: ToastService, useValue: toast },
      ],
    });
    const fixture = TestBed.createComponent(AttendanceReportComponent);
    fixture.componentInstance.groupId.set('1');
    fixture.componentInstance.download();
    expect(toast.show).toHaveBeenCalledWith('Error de reporte');
  });

  it('limits attendance reports to 14 days', () => {
    const report = { options: () => ({ subscribe: () => {} }) };
    const attendance = vi.fn();
    TestBed.configureTestingModule({
      imports: [AttendanceReportComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ReportsService, useValue: { ...report, attendance } },
        { provide: ToastService, useValue: { show: () => undefined } },
      ],
    });
    const fixture = TestBed.createComponent(AttendanceReportComponent);
    fixture.componentInstance.groupId.set('1');
    fixture.componentInstance.days.set(15);
    fixture.componentInstance.download();
    expect(attendance).not.toHaveBeenCalled();
    expect(fixture.nativeElement.querySelector('#attendance-days').getAttribute('max')).toBe('14');
  });
});
