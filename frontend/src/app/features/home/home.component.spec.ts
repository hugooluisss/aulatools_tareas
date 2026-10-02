import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { TokenStorageService } from '../../core/auth/token-storage.service';
import { TasksService } from '../tasks/tasks.service';
import { HomeComponent } from './home.component';

describe('HomeComponent', () => {
  let fixture: ComponentFixture<HomeComponent>;
  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HomeComponent],
      providers: [
        { provide: TokenStorageService, useValue: { getRole: () => 'student' } },
        {
          provide: TasksService,
          useValue: {
            myTasks: () => of({ data: [], meta: { total: 3 } }),
            subjects: () => of({ data: [], meta: { total: 0 } }),
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
});
