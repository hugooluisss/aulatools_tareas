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
    service.enroll(3, 9).subscribe();
    const request = http.expectOne('http://localhost:8080/subjects/3/students');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ student_id: 9 });
    request.flush({ data: { student_id: 9, subject_id: 3 } });
  });
});
