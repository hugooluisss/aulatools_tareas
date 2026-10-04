import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { AdminTaskOverviewComponent } from './admin-task-overview.component';

describe('AdminTaskOverviewComponent', () => {
  it('renders overview rows and paginates them locally when filters and pages change', () => {
    const overview = vi.fn().mockReturnValue(
      of([
        {
          delivery_id: 3,
          status: 'graded',
          task: { id: 4, title: 'Ensayo', description: 'Escribe un ensayo.', due_at: '2026-10-03' },
          student: { id: 5, first_name: 'Ana', last_name: 'López' },
          subject: { id: 6, code: 'ESP', name: 'Español' },
        },
      ]),
    );
    const deliveryStatuses = vi.fn().mockReturnValue(
      of([
        { code: 'graded', label: 'Calificada', color: '#CFE2FF', text_color: '#084298' },
        { code: 'pending', label: 'Pendiente', color: '#FFF3CD', text_color: '#664D03' },
      ]),
    );
    TestBed.configureTestingModule({
      imports: [AdminTaskOverviewComponent],
      providers: [{ provide: TasksService, useValue: { overview, deliveryStatuses } }],
    });

    const fixture = TestBed.createComponent(AdminTaskOverviewComponent);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Ana López');
    expect(fixture.nativeElement.textContent).toContain('Ensayo');
    expect(fixture.nativeElement.textContent).toContain('ESP · Español');
    [...fixture.nativeElement.querySelectorAll('[role="button"]')]
      .find((element) => element.textContent.includes('Ensayo'))
      .click();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]').getAttribute('aria-modal')).toBe(
      'true',
    );
    expect(fixture.nativeElement.textContent).toContain('Escribe un ensayo.');
    expect(fixture.nativeElement.textContent).toContain('Calificada');
    expect(fixture.nativeElement.textContent).not.toContain('Eliminar');
    fixture.componentInstance.closeDetailsOnEscape();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).toBeNull();
    expect(overview).toHaveBeenLastCalledWith('', []);
    expect(deliveryStatuses).toHaveBeenCalledOnce();
    expect(fixture.nativeElement.textContent).toContain('Calificada');
    expect(
      fixture.nativeElement.querySelector('.status-badge').style.getPropertyValue('--status-color'),
    ).toBe('#CFE2FF');
    expect(
      fixture.nativeElement
        .querySelector('.status-badge')
        .style.getPropertyValue('--status-text-color'),
    ).toBe('#084298');

    fixture.componentInstance.search = 'Ana';
    fixture.componentInstance.selectedStatuses = ['graded'];
    fixture.componentInstance.filtersChanged();
    expect(overview).toHaveBeenLastCalledWith('Ana', ['graded']);

    fixture.componentInstance.loadPage(2);
    expect(overview).toHaveBeenCalledTimes(2);
    expect(fixture.componentInstance.tablePage().items).toEqual([]);
  });

  it('toggles multiple statuses and resets to page one', () => {
    const rows = Array.from({ length: 21 }, (_, index) => ({
      delivery_id: index + 1,
      status: 'pending' as const,
      task: {
        id: index + 1,
        title: `Tarea ${index + 1}`,
        description: `Descripción ${index + 1}`,
        due_at: '2026-10-03',
      },
      student: { id: index + 1, first_name: 'Ana', last_name: 'López' },
      subject: { id: index + 1, code: 'ESP', name: 'Español' },
    }));
    const overview = vi.fn().mockReturnValue(of(rows));
    const deliveryStatuses = vi.fn().mockReturnValue(
      of([
        { code: 'pending', label: 'Pendiente', color: '#FFF3CD', text_color: '#664D03' },
        { code: 'graded', label: 'Calificada', color: '#CFE2FF', text_color: '#084298' },
      ]),
    );
    TestBed.configureTestingModule({
      imports: [AdminTaskOverviewComponent],
      providers: [{ provide: TasksService, useValue: { overview, deliveryStatuses } }],
    });

    const fixture = TestBed.createComponent(AdminTaskOverviewComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance.tablePage().items).toHaveLength(20);
    fixture.componentInstance.loadPage(2);
    expect(fixture.componentInstance.tablePage().items).toHaveLength(1);
    fixture.componentInstance.loadPage(3);
    fixture.componentInstance.toggleStatus('pending');
    fixture.componentInstance.toggleStatus('graded');
    fixture.detectChanges();

    expect(overview).toHaveBeenLastCalledWith('', ['pending', 'graded']);
    expect(fixture.componentInstance.page()).toBe(1);
    expect(
      fixture.nativeElement.querySelector('input[type="search"]').getAttribute('placeholder'),
    ).toBe('Buscar estudiante o materia');
    const pendingButton = [...fixture.nativeElement.querySelectorAll('button')].find((button) =>
      button.textContent.includes('Pendiente'),
    );
    expect(pendingButton.getAttribute('aria-pressed')).toBe('true');
  });
});
