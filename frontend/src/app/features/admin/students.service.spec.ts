import { environment } from '../../../environments/environment';
import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { StudentsService } from './students.service';

describe('StudentsService', () => {
  let service: StudentsService;
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(StudentsService);
    http = TestBed.inject(HttpTestingController);
  });
  afterEach(() => http.verify());
  it('creates students using the API contract fields', () => {
    const data = {
      first_name: 'Ana',
      last_name: 'Lopez',
      email: 'ana@example.mx',
      password: 'clave1234',
      enrollment_number: 'A1',
      birth_date: '2010-01-01',
    };
    service.save(null, data).subscribe();
    const request = http.expectOne(`${environment.apiUrl}/users/students`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(data);
    request.flush({ id: 1 });
  });
});
