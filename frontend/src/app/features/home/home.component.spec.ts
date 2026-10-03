import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { TokenStorageService } from '../../core/auth/token-storage.service';
import { TasksService } from '../tasks/tasks.service';
import { HomeComponent } from './home.component';

describe('HomeComponent', () => {
  let fixture: ComponentFixture<HomeComponent>;
  let role = 'student';
  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HomeComponent],
      providers: [
        { provide: TokenStorageService, useValue: { getRole: () => role } },
        {
          provide: TasksService,
          useValue: {
            myTasks: () => of({ items: [], page: 1, per_page: 20, total: 3, total_pages: 1 }),
            subjects: () => of({ items: [], page: 1, per_page: 20, total: 37, total_pages: 2 }),
          },
        },
      ],
    });
    fixture = TestBed.createComponent(HomeComponent);
    fixture.detectChanges();
  });
  it('renders the welcome dashboard and its school sections', () => {
    const text = fixture.nativeElement.textContent;
    expect(text).toContain('¡Bienvenido!');
    expect(text).toContain('Tareas pendientes');
    expect(text).toContain('3');
    expect(text).toContain('Calendario');
    expect(text).toContain('Avisos');
  });

  it('uses the total subjects header for the school KPI', () => {
    role = 'admin';
    fixture = TestBed.createComponent(HomeComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance.count()).toBe(37);
  });
});
