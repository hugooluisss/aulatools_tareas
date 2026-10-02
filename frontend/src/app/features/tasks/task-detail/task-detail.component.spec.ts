import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
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

  it('shows teacher, grade, delivery state and comments link', () => {
    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Ana López');
    expect(text).toContain('95');
    expect(text).toContain('Calificada');
    expect(text).toContain('Ver comentarios');
  });
});
