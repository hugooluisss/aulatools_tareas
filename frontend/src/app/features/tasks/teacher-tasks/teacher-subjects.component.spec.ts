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
        { provide: CyclesService, useValue: { list: () => of([{ id: '14', status: 'active' }]) } },
        {
          provide: TasksService,
          useValue: {
            subjects: () => of([{ id: '1', name: 'Ciencias', status: 'active' }]),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TeacherSubjectsComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Ciencias');
  });
});
