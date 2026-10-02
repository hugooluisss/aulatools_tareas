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
    const request = http.expectOne(`${environment.apiUrl}/me/tasks?status=pending`);
    expect(request.request.method).toBe('GET');
    request.flush([]);
  });

  it('posts comments to the delivery thread', () => {
    service.addComment(9, 'Hola').subscribe();
    const request = http.expectOne(`${environment.apiUrl}/deliveries/9/comments`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ body: 'Hola' });
    request.flush({ id: 1 });
  });
});
