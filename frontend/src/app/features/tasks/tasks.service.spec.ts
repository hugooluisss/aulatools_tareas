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

  it('loads all student tasks by default without status or cycle filters', () => {
    service.myTasks().subscribe();
    const request = http.expectOne(`${environment.apiUrl}/me/tasks?page=1&per_page=20`);
    expect(request.request.method).toBe('GET');
    request.flush({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  });

  it('sends student search, multiple statuses, cycle and page filters', () => {
    service.myTasks(['pending', 'graded'], ' math ', 14, 2).subscribe();
    const request = http.expectOne(
      `${environment.apiUrl}/me/tasks?page=2&per_page=20&status=pending,graded&search=math&cycle_id=14`,
    );
    request.flush({ items: [], page: 2, per_page: 20, total: 0, total_pages: 1 });
  });

  it('posts comments to the delivery thread', () => {
    service.addComment(9, 'Hola').subscribe();
    const request = http.expectOne(`${environment.apiUrl}/deliveries/9/comments`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ body: 'Hola' });
    request.flush({ id: 1 });
  });

  it('loads the complete delivery history', () => {
    service.deliveryHistory(9).subscribe((response) => {
      expect(response.items[0].type).toBe('comment_added');
      expect(response.items[0].actor?.name).toBe('Ana Pérez');
    });
    const request = http.expectOne(`${environment.apiUrl}/deliveries/9/history`);
    expect(request.request.method).toBe('GET');
    request.flush({
      items: [
        {
          id: 2,
          type: 'comment_added',
          actor: { id: 4, name: 'Ana Pérez', role: 'teacher' },
          created_at: '2026-10-04 12:00:00',
          payload: { comment_id: 3 },
        },
      ],
    });
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
