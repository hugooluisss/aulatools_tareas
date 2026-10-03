import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { CyclesService } from '../../admin/cycles.service';
import { TeacherSubjectDetailComponent } from './teacher-subject-detail.component';
import { TokenStorageService } from '../../../core/auth/token-storage.service';

describe('TeacherSubjectDetailComponent', () => {
  it('shows students and task management actions', () => {
    TestBed.configureTestingModule({
      imports: [TeacherSubjectDetailComponent],
      providers: [
        provideRouter([]),
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '2' } } } },
        {
          provide: TasksService,
          useValue: {
            students: () =>
              of({
                items: [{ id: 1, first_name: 'Leo', last_name: 'Ruiz', enrollment_number: '1' }],
                page: 1,
                per_page: 20,
                total: 1,
                total_pages: 1,
              }),
            tasks: () =>
              of({
                items: [
                  {
                    id: 2,
                    subject_id: 2,
                    name: 'Proyecto',
                    description: '',
                    due_at: '2026-10-08',
                    status: 'active',
                  },
                ],
                page: 1,
                per_page: 20,
                total: 1,
                total_pages: 1,
              }),
          },
        },
        {
          provide: CyclesService,
          useValue: { all: () => of([{ id: '14', name: '2026', status: 'active' }]) },
        },
      ],
    });
    const fixture = TestBed.createComponent(TeacherSubjectDetailComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Leo Ruiz');
    expect(fixture.nativeElement.textContent).toContain('Proyecto');
    const studentHeaders = fixture.nativeElement.querySelectorAll('app-data-table th');
    expect(studentHeaders[0].textContent).toContain('Matrícula');
    expect(studentHeaders[1].textContent).toContain('Estudiante');
    expect(fixture.nativeElement.querySelector('[aria-label="Agregar tarea"]')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('[aria-label="Ver entregas"]')).not.toBeNull();
  });
});
