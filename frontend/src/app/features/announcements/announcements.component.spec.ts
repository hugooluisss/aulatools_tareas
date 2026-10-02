import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { TokenStorageService } from '../../core/auth/token-storage.service';
import { AnnouncementsService } from './announcements.service';
import { AnnouncementsComponent } from './announcements.component';

describe('AnnouncementsComponent', () => {
  let fixture: ComponentFixture<AnnouncementsComponent>;
  const api = { active: vi.fn().mockReturnValue(of({ data: [] })) };

  beforeEach(() => {
    api.active.mockClear();
    TestBed.configureTestingModule({
      imports: [AnnouncementsComponent],
      providers: [
        { provide: AnnouncementsService, useValue: api },
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
      ],
    });
    fixture = TestBed.createComponent(AnnouncementsComponent);
    fixture.detectChanges();
  });

  it('loads only the active list for a teacher', () => {
    expect(api.active).toHaveBeenCalledOnce();
    expect(fixture.nativeElement.textContent).toContain('Avisos vigentes');
    expect(fixture.nativeElement.querySelector('form')).toBeNull();
  });
});
