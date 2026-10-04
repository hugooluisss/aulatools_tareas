import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { ReportsService } from './reports.service';

describe('ReportsService', () => {
  let service: ReportsService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ReportsService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('loads report options and paginated subject students', () => {
    service.options().subscribe();
    http.expectOne(`${environment.apiUrl}/reports/options`).flush({ groups: [] });
    service.students(8).subscribe();
    const request = http.expectOne(
      (req) => req.url === `${environment.apiUrl}/subjects/8/students`,
    );
    expect(request.request.params.get('per_page')).toBe('100');
    request.flush({ items: [], page: 1, per_page: 100, total: 0, total_pages: 1 });
  });

  it('posts selected IDs and requests a PDF blob', () => {
    service.taskReportCard(8, [2, 3]).subscribe();
    const request = http.expectOne(`${environment.apiUrl}/reports/task-report-card`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ subject_id: 8, student_ids: [2, 3] });
    request.flush(new Blob(['pdf']));
  });
});
