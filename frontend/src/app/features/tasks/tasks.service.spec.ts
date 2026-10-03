import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { TasksService } from './tasks.service';

describe('TasksService', () => {
  let service: TasksService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(TasksService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('uses the documented student task filter endpoint', () => {
    service.myTasks().subscribe();
    const request = http.expectOne(
      `${environment.apiUrl}/me/tasks?status=pending&page=1&per_page=20`,
    );
    expect(request.request.method).toBe('GET');
    request.flush({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  });

  it('posts comments to the delivery thread', () => {
    service.addComment(9, 'Hola').subscribe();
    const request = http.expectOne(`${environment.apiUrl}/deliveries/9/comments`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ body: 'Hola' });
    request.flush({ id: 1 });
  });

  it('requests the complete task overview with search and multiple status filters', () => {
    service.overview(' Ana ', ['pending', 'graded']).subscribe();
    const request = http.expectOne(
      `${environment.apiUrl}/tasks/overview?search=Ana&status=pending,graded`,
    );
    expect(request.request.method).toBe('GET');
    request.flush([]);
  });

  it('loads status labels and colors from the API', () => {
    service.deliveryStatuses().subscribe((statuses) => expect(statuses[0].label).toBe('Pendiente'));
    const request = http.expectOne(`${environment.apiUrl}/tasks/statuses`);
    expect(request.request.method).toBe('GET');
    request.flush([{ code: 'pending', label: 'Pendiente', color: '#FFF3CD' }]);
  });
});
