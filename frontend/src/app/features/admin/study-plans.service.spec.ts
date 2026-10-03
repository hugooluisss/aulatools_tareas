import { environment } from '../../../environments/environment';
import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { StudyPlansService } from './study-plans.service';

describe('StudyPlansService', () => {
  let service: StudyPlansService;
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(StudyPlansService);
    http = TestBed.inject(HttpTestingController);
  });
  afterEach(() => http.verify());

  it('lists and creates plans', () => {
    service.list().subscribe();
    http.expectOne(`${environment.apiUrl}/study-plans`).flush([]);
    const plan = { code: 'BAS-2026', name: 'Primaria', status: 'active' as const };
    service.save(null, plan).subscribe();
    const request = http.expectOne(`${environment.apiUrl}/study-plans`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(plan);
    request.flush({ id: '1', ...plan });
  });
});
