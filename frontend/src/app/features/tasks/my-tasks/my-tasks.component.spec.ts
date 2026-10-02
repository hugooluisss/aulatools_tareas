import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { MyTasksComponent } from './my-tasks.component';

describe('MyTasksComponent', () => {
  let fixture: ComponentFixture<MyTasksComponent>;
  const tasks = { myTasks: vi.fn().mockReturnValue(of({ data: [], meta: {} })) };

  beforeEach(() => {
    tasks.myTasks.mockClear();
    TestBed.configureTestingModule({
      imports: [MyTasksComponent],
      providers: [{ provide: TasksService, useValue: tasks }, provideRouter([])],
    });
    fixture = TestBed.createComponent(MyTasksComponent);
    fixture.detectChanges();
  });

  it('loads pending tasks by default', () => {
    expect(tasks.myTasks).toHaveBeenCalledWith('pending');
    expect(fixture.nativeElement.textContent).toContain('Mis tareas');
  });
});
