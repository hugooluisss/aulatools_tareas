import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { CyclesService } from './cycles.service';

describe('CyclesService', () => {
  let service: CyclesService;
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(CyclesService);
    http = TestBed.inject(HttpTestingController);
  });
  afterEach(() => http.verify());
  it('uses the finalize action endpoint', () => {
    service.finish(4).subscribe();
    const request = http.expectOne('http://localhost:8080/cycles/4/finish');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({});
    request.flush({ data: { id: 4, status: 'finished' } });
  });
});
