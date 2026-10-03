import { environment } from '../../../environments/environment';
import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { SubjectsService } from './subjects.service';

describe('SubjectsService', () => {
  let service: SubjectsService;
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(SubjectsService);
    http = TestBed.inject(HttpTestingController);
  });
  afterEach(() => http.verify());
  it('enrolls a student in one subject', () => {
    service.enroll(3, 9, 14).subscribe();
    const request = http.expectOne(`${environment.apiUrl}/subjects/3/students`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ student_id: 9, cycle_id: 14 });
    request.flush({ student_id: 9, subject_id: 3, cycle_id: 14 });
  });

  it('sends the cycle when removing a student from a subject', () => {
    service.unenroll(3, 9, 14).subscribe();
    const request = http.expectOne(`${environment.apiUrl}/subjects/3/students/9?cycle_id=14`);
    expect(request.request.method).toBe('DELETE');
    request.flush(null);
  });

  it('sends the selected cycle when enrolling students in bulk', () => {
    service.enrollBulk(31, 14, [57]).subscribe();
    const request = http.expectOne(`${environment.apiUrl}/subjects/31/students/bulk`);
    expect(request.request.body).toEqual({ cycle_id: 14, student_ids: [57] });
    request.flush([]);
  });
});
