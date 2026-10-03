import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { TeacherSubjectsComponent } from './teacher-subjects.component';
import { CyclesService } from '../../admin/cycles.service';
import { TokenStorageService } from '../../../core/auth/token-storage.service';

describe('TeacherSubjectsComponent', () => {
  it('renders assigned subjects', () => {
    TestBed.configureTestingModule({
      imports: [TeacherSubjectsComponent],
      providers: [
        provideRouter([]),
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
        { provide: CyclesService, useValue: { all: () => of([{ id: '14', status: 'active' }]) } },
        {
          provide: TasksService,
          useValue: {
            subjects: () => of({ items: [{ id: '1', name: 'Ciencias', status: 'active' }], page: 1, per_page: 20, total: 1, total_pages: 1 }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TeacherSubjectsComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Ciencias');
    expect(fixture.nativeElement.querySelector('app-data-table')).toBeNull();
  });

  it('renders admin subjects in a paginated table with a detail action', () => {
    TestBed.configureTestingModule({
      imports: [TeacherSubjectsComponent],
      providers: [
        provideRouter([]),
        { provide: TokenStorageService, useValue: { getRole: () => 'admin' } },
        { provide: CyclesService, useValue: { all: () => of([{ id: '14', status: 'active' }]) } },
        {
          provide: TasksService,
          useValue: {
            subjects: () => of({ items: [{ id: '1', name: 'Ciencias', status: 'active' }], page: 1, per_page: 20, total: 1, total_pages: 1 }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TeacherSubjectsComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('app-data-table')).not.toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Nombre');
    expect(fixture.nativeElement.textContent).toContain('Estado');
    expect(fixture.nativeElement.querySelector('[aria-label="Ver estudiantes y tareas"]')).not.toBeNull();
  });
});
