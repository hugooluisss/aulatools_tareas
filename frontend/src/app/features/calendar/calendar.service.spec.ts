import { environment } from '../../../environments/environment';
import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { CalendarService } from './calendar.service';

describe('CalendarService', () => {
  let service: CalendarService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(CalendarService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('loads the requested calendar range', () => {
    service.list('2026-10-01T00:00:00Z', '2026-11-01T00:00:00Z').subscribe();
    const request = http.expectOne((req) => req.url === `${environment.apiUrl}/calendar`);
    expect(request.request.params.get('from')).toBe('2026-10-01T00:00:00Z');
    expect(request.request.params.get('to')).toBe('2026-11-01T00:00:00Z');
    request.flush([]);
  });

  it('creates and deletes events', () => {
    const event = {
      subject_id: null,
      title: 'Junta',
      description: '',
      starts_at: '2026-10-01T10:00:00Z',
      ends_at: '2026-10-01T11:00:00Z',
    };
    service.save(null, event).subscribe();
    const create = http.expectOne(`${environment.apiUrl}/calendar/events`);
    expect(create.request.method).toBe('POST');
    create.flush({ id: 1, ...event });
    service.remove(1).subscribe();
    const remove = http.expectOne(`${environment.apiUrl}/calendar/events/1`);
    expect(remove.request.method).toBe('DELETE');
    remove.flush(null);
  });
});
