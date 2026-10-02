import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { GroupsService } from './groups.service';

describe('GroupsService', () => {
  let service: GroupsService;
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(GroupsService);
    http = TestBed.inject(HttpTestingController);
  });
  afterEach(() => http.verify());
  it('enrolls a student in all subjects of a group', () => {
    service.enroll(2, 8).subscribe();
    const request = http.expectOne('http://localhost:8080/groups/2/students');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ student_id: 8 });
    request.flush({ data: { student_id: 8, group_id: 2, subject_ids: [3] } });
  });
});
