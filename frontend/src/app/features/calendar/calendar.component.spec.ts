import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { CalendarService } from './calendar.service';
import { CalendarComponent } from './calendar.component';
import { SubjectsService } from '../admin/subjects.service';
import { TokenStorageService } from '../../core/auth/token-storage.service';

describe('CalendarComponent', () => {
  let fixture: ComponentFixture<CalendarComponent>;
  const calendar = {
    list: vi
      .fn()
      .mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 })),
    events: vi
      .fn()
      .mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 })),
    save: vi.fn().mockReturnValue(of({})),
  };

  beforeEach(() => {
    calendar.list.mockClear();
    calendar.save.mockClear();
    TestBed.configureTestingModule({
      imports: [CalendarComponent],
      providers: [
        { provide: CalendarService, useValue: calendar },
        { provide: SubjectsService, useValue: { all: () => of([]) } },
        { provide: TokenStorageService, useValue: { getRole: () => 'student' } },
      ],
    });
  });

  it('loads and shows a monthly grid', () => {
    fixture = TestBed.createComponent(CalendarComponent);
    fixture.detectChanges();
    expect(calendar.list).toHaveBeenCalledOnce();
    expect(fixture.nativeElement.textContent).toContain('Calendario');
    expect(fixture.nativeElement.querySelectorAll('[role="gridcell"]').length).toBe(42);
  });

  it('opens and closes the event form in a modal', () => {
    TestBed.overrideProvider(TokenStorageService, { useValue: { getRole: () => 'admin' } });
    fixture = TestBed.createComponent(CalendarComponent);
    fixture.detectChanges();
    fixture.nativeElement.querySelector('button').click();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('[role="grid"]')).not.toBeNull();
    fixture.nativeElement.querySelector('.modal-footer .btn-outline-secondary').click();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).toBeNull();
  });

  it('shows a selected day list and opens an accessible item detail modal', () => {
    const item = {
      id: 42,
      type: 'task_due' as const,
      title: 'Entrega de ciencias',
      description: 'Preparar el experimento',
      starts_at: '2026-10-03',
      ends_at: '2026-10-03',
      subject_id: 7,
      task_id: 42,
    };
    calendar.list.mockReturnValueOnce(
      of({ items: [item], page: 1, per_page: 20, total: 1, total_pages: 1 }),
    );
    fixture = TestBed.createComponent(CalendarComponent);
    fixture.detectChanges();
    const dayCell = Array.from(fixture.nativeElement.querySelectorAll('[role="gridcell"]')).find(
      (cell: any) => cell.textContent.includes('Entrega de ciencias'),
    ) as HTMLElement;
    dayCell.click();
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Eventos del');
    fixture.nativeElement.querySelector('.calendar-page__event-title').click();
    fixture.detectChanges();
    const dialog = fixture.nativeElement.querySelector('[role="dialog"]');
    expect(dialog.getAttribute('aria-modal')).toBe('true');
    expect(dialog.textContent).toContain('Entrega de ciencias');
    expect(dialog.textContent).toContain('Tarea por entregar');
    expect(dialog.textContent).toContain('Preparar el experimento');
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).toBeNull();
  });
});
