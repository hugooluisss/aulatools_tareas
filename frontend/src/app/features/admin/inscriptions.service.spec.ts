import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { environment } from '../../../environments/environment';
import { InscriptionsService } from './inscriptions.service';

describe('InscriptionsService', () => {
  let service: InscriptionsService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(InscriptionsService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('lists and mutates bare inscription resources', () => {
    service.list({ cycle_id: 14, student_id: 57 }).subscribe((rows) => expect(rows).toEqual([]));
    const list = http.expectOne(`${environment.apiUrl}/enrollments?cycle_id=14&student_id=57`);
    expect(list.request.method).toBe('GET');
    list.flush([]);

    service.create({ student_id: 57, cycle_id: 14, group_id: 11 }).subscribe();
    const create = http.expectOne(`${environment.apiUrl}/enrollments`);
    expect(create.request.method).toBe('POST');
    expect(create.request.body).toEqual({ student_id: 57, cycle_id: 14, group_id: 11 });
    create.flush({ id: 1 });

    service.update(1, 12).subscribe();
    const update = http.expectOne(`${environment.apiUrl}/enrollments/1`);
    expect(update.request.method).toBe('PUT');
    expect(update.request.body).toEqual({ group_id: 12 });
    update.flush({ id: 1 });

    service.remove(1).subscribe();
    const remove = http.expectOne(`${environment.apiUrl}/enrollments/1`);
    expect(remove.request.method).toBe('DELETE');
    remove.flush(null);
  });

  it('lists candidates and creates bulk inscriptions with bare responses', () => {
    service.aspirants().subscribe((rows) => expect(rows).toEqual([]));
    http.expectOne(`${environment.apiUrl}/enrollments/aspirants`).flush([]);
    service.reenrollable().subscribe((rows) => expect(rows).toEqual([]));
    http.expectOne(`${environment.apiUrl}/enrollments/reenrollable`).flush([]);
    service
      .bulk({ type: 'reenrollment', student_ids: [57], cycle_id: 14, group_id: 11 })
      .subscribe((rows) => expect(rows).toEqual([]));
    const request = http.expectOne(`${environment.apiUrl}/enrollments/bulk`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({
      type: 'reenrollment',
      student_ids: [57],
      cycle_id: 14,
      group_id: 11,
    });
    request.flush([]);
  });
});
