import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { TeacherSubjectDetailComponent } from './teacher-subject-detail.component';

describe('TeacherSubjectDetailComponent', () => {
  it('shows students and task management actions', () => {
    TestBed.configureTestingModule({
      imports: [TeacherSubjectDetailComponent],
      providers: [
        provideRouter([]),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '2' } } } },
        {
          provide: TasksService,
          useValue: {
            students: () =>
              of({
                data: [{ id: 1, first_name: 'Leo', last_name: 'Ruiz', enrollment_number: 'A1' }],
              }),
            tasks: () =>
              of({
                data: [{ id: 2, name: 'Proyecto', description: '', due_at: '', status: 'active' }],
              }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TeacherSubjectDetailComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Leo Ruiz');
    expect(fixture.nativeElement.textContent).toContain('Proyecto');
    expect(fixture.nativeElement.querySelector('[aria-label="Agregar tarea"]')).not.toBeNull();
  });
});
