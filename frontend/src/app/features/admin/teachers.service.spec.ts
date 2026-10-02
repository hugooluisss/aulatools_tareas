import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TeachersService } from './teachers.service';

describe('TeachersService', () => {
  let service: TeachersService;
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(TeachersService);
    http = TestBed.inject(HttpTestingController);
  });
  afterEach(() => http.verify());
  it('resets a teacher password through the admin endpoint', () => {
    service.reset(7, 'newpass123').subscribe();
    const request = http.expectOne('http://localhost:8080/users/7/password');
    expect(request.request.method).toBe('PUT');
    expect(request.request.body).toEqual({ new_password: 'newpass123' });
    request.flush({ message: 'Password updated' });
  });
});
