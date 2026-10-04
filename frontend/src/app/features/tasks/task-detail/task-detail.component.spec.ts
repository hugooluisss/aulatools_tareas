import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { DeliveryHistoryEvent, TasksService } from '../tasks.service';
import { TaskDetailComponent } from './task-detail.component';

describe('TaskDetailComponent', () => {
  let fixture: ComponentFixture<TaskDetailComponent>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [TaskDetailComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '7' } } } },
        {
          provide: TasksService,
          useValue: {
            deliveryHistory: () => of({ items: [] as DeliveryHistoryEvent[] }),
            markCommentsRead: () => of({}),
            comments: () => of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 }),
            deliveryStatuses: () =>
              of([{ code: 'graded', label: 'Calificada', color: '#fff', text_color: '#000' }]),
            myTask: () =>
              of({
                task: {
                  id: 1,
                  name: 'Ensayo',
                  description: 'Tema',
                  due_at: '2026-10-01',
                  status: 'active',
                },
                subject: { id: 2, name: 'Historia' },
                teacher: { id: 3, first_name: 'Ana', last_name: 'López' },
                delivery: {
                  id: 7,
                  status: 'graded',
                  grade: 95,
                  delivered_at: '2026-09-30',
                  overdue: false,
                },
              }),
          },
        },
      ],
    });
    fixture = TestBed.createComponent(TaskDetailComponent);
    fixture.detectChanges();
  });

  it('shows teacher, grade, delivery state, dates and embedded comments', () => {
    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Ana López');
    expect(text).toContain('95');
    expect(text).toContain('Calificada');
    expect(text).toContain('Comentarios');
    expect(text).toContain('Creada');
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).toBeNull();
  });
});
