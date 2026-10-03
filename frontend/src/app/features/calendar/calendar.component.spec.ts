import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { CalendarService } from './calendar.service';
import { CalendarComponent } from './calendar.component';
import { SubjectsService } from '../admin/subjects.service';
import { TokenStorageService } from '../../core/auth/token-storage.service';

describe('CalendarComponent', () => {
  let fixture: ComponentFixture<CalendarComponent>;
  const calendar = {
    list: vi.fn().mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 })),
    events: vi.fn().mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 })),
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
});
