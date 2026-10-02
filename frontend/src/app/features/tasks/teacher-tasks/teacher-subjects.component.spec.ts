import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { TeacherSubjectsComponent } from './teacher-subjects.component';

describe('TeacherSubjectsComponent', () => {
  it('renders assigned subjects', () => {
    TestBed.configureTestingModule({
      imports: [TeacherSubjectsComponent],
      providers: [
        provideRouter([]),
        {
          provide: TasksService,
          useValue: {
            subjects: () => of([{ id: 1, name: 'Ciencias', status: 'in_progress' }]),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TeacherSubjectsComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Ciencias');
  });
});
