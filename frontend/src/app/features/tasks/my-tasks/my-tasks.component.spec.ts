import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { CyclesService } from '../../admin/cycles.service';
import { MyTasksComponent } from './my-tasks.component';

describe('MyTasksComponent', () => {
  let fixture: ComponentFixture<MyTasksComponent>;
  const tasks = {
    myTasks: vi
      .fn()
      .mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 })),
    deliveryStatuses: vi.fn().mockReturnValue(
      of([
        { code: 'pending', label: 'Pendiente', color: '#fff', text_color: '#000' },
        { code: 'graded', label: 'Calificada', color: '#fff', text_color: '#000' },
      ]),
    ),
  };

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

  it('loads all statuses for the only active cycle by default', () => {
    expect(tasks.myTasks).toHaveBeenCalledWith([], '', 14, 1);
    expect(fixture.nativeElement.textContent).toContain('Mis tareas');
    expect(fixture.nativeElement.textContent).toContain('Pendiente');
    expect(fixture.componentInstance.cycleId()).toBe(14);
  });

  it('resets pagination when search and status filters change', () => {
    fixture.componentInstance.page.set(3);
    fixture.componentInstance.search = 'fracciones';
    fixture.componentInstance.filterChanged();
    expect(tasks.myTasks).toHaveBeenLastCalledWith([], 'fracciones', 14, 1);
    fixture.componentInstance.toggleStatus('graded');
    expect(tasks.myTasks).toHaveBeenLastCalledWith(['graded'], 'fracciones', 14, 1);
  });

  it('shows the cycle selector with all cycles for multiple active cycles', () => {
    fixture.destroy();
    TestBed.resetTestingModule();
    TestBed.configureTestingModule({
      imports: [MyTasksComponent],
      providers: [
        { provide: TasksService, useValue: tasks },
        {
          provide: CyclesService,
          useValue: {
            all: () =>
              of([
                { id: '14', name: '2026', status: 'active' },
                { id: '15', name: '2027', status: 'active' },
              ]),
          },
        },
        provideRouter([]),
      ],
    });
    fixture = TestBed.createComponent(MyTasksComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Todos los ciclos');
    expect(tasks.myTasks).toHaveBeenLastCalledWith([], '', undefined, 1);
  });
});
