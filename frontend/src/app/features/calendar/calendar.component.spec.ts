import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { CalendarService } from './calendar.service';
import { CalendarComponent } from './calendar.component';
import { SubjectsService } from '../admin/subjects.service';
import { TokenStorageService } from '../../core/auth/token-storage.service';

describe('CalendarComponent', () => {
  let fixture: ComponentFixture<CalendarComponent>;
  const calendar = {
    list: vi.fn().mockReturnValue(of({ data: [] })),
    events: vi.fn().mockReturnValue(of({ data: [] })),
  };

  beforeEach(() => {
    calendar.list.mockClear();
    TestBed.configureTestingModule({
      imports: [CalendarComponent],
      providers: [
        { provide: CalendarService, useValue: calendar },
        { provide: SubjectsService, useValue: { list: () => of({ data: [] }) } },
        { provide: TokenStorageService, useValue: { getRole: () => 'student' } },
      ],
    });
    fixture = TestBed.createComponent(CalendarComponent);
    fixture.detectChanges();
  });

  it('loads and shows a monthly grid', () => {
    expect(calendar.list).toHaveBeenCalledOnce();
    expect(fixture.nativeElement.textContent).toContain('Calendario');
    expect(fixture.nativeElement.querySelectorAll('[role="gridcell"]').length).toBe(42);
  });
});
