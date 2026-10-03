import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { TokenStorageService } from '../../core/auth/token-storage.service';
import { AnnouncementsService } from './announcements.service';
import { AnnouncementsComponent } from './announcements.component';

describe('AnnouncementsComponent', () => {
  let fixture: ComponentFixture<AnnouncementsComponent>;
  const page = { items: [], page: 1, per_page: 20, total: 0, total_pages: 1 };
  const api = { active: vi.fn().mockReturnValue(of(page)), list: vi.fn().mockReturnValue(of(page)) };

  beforeEach(() => {
    api.active.mockClear();
    api.list.mockClear();
    TestBed.configureTestingModule({
      imports: [AnnouncementsComponent],
      providers: [
        { provide: AnnouncementsService, useValue: api },
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
      ],
    });
  });

  it('loads only the active list for a teacher', () => {
    fixture = TestBed.createComponent(AnnouncementsComponent);
    fixture.detectChanges();
    expect(api.active).toHaveBeenCalledOnce();
    expect(fixture.nativeElement.textContent).toContain('Avisos vigentes');
    expect(fixture.nativeElement.querySelector('form')).toBeNull();
  });

  it('opens and closes the announcement form in a modal for admins', () => {
    TestBed.overrideProvider(TokenStorageService, { useValue: { getRole: () => 'admin' } });
    fixture = TestBed.createComponent(AnnouncementsComponent);
    fixture.detectChanges();
    fixture.nativeElement.querySelector('button').click();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('.announcements-page__list')).not.toBeNull();
    fixture.nativeElement.querySelector('.modal-footer .btn-outline-secondary').click();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).toBeNull();
  });
});
