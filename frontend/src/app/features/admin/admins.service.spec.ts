import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { environment } from '../../../environments/environment';
import { AdminsService } from './admins.service';

describe('AdminsService', () => {
  let service: AdminsService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideHttpClient(), provideHttpClientTesting()] });
    service = TestBed.inject(AdminsService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('lists a page and all administrators', () => {
    service.list().subscribe();
    const page = http.expectOne(`${environment.apiUrl}/users/admins?page=1&per_page=20`);
    expect(page.request.method).toBe('GET');
    page.flush({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });

    service.all().subscribe((items) => expect(items).toEqual([]));
    const all = http.expectOne(`${environment.apiUrl}/users/admins?per_page=100`);
    all.flush({ items: [], page: 1, per_page: 100, total: 0, total_pages: 1 });
  });
});
