import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { AnnouncementsService } from './announcements.service';

describe('AnnouncementsService', () => {
  let service: AnnouncementsService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(AnnouncementsService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('loads active announcements for the community', () => {
    service.active().subscribe();
    const request = http.expectOne('http://localhost:8080/announcements/active');
    expect(request.request.method).toBe('GET');
    request.flush({ data: [] });
  });

  it('updates an announcement', () => {
    service
      .save(3, { title: 'Aviso', body: 'Texto', starts_on: '2026-10-01', ends_on: '2026-10-03' })
      .subscribe();
    const request = http.expectOne('http://localhost:8080/announcements/3');
    expect(request.request.method).toBe('PUT');
    request.flush({ data: { id: 3 } });
  });
});
