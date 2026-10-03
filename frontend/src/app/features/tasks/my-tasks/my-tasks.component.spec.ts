import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { CyclesService } from '../../admin/cycles.service';
import { MyTasksComponent } from './my-tasks.component';

describe('MyTasksComponent', () => {
  let fixture: ComponentFixture<MyTasksComponent>;
  const tasks = { myTasks: vi.fn().mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 })) };

  beforeEach(() => {
    tasks.myTasks.mockClear();
    TestBed.configureTestingModule({
      imports: [MyTasksComponent],
      providers: [
        { provide: TasksService, useValue: tasks },
        { provide: CyclesService, useValue: { all: () => of([{ id: '14', status: 'active' }]) } },
        provideRouter([]),
      ],
    });
    fixture = TestBed.createComponent(MyTasksComponent);
    fixture.detectChanges();
  });

  it('loads pending tasks by default', () => {
    expect(tasks.myTasks).toHaveBeenCalledWith('pending', 14, 1);
    expect(fixture.nativeElement.textContent).toContain('Mis tareas');
  });
});
