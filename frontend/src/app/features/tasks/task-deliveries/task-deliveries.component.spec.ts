import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { TaskDeliveriesComponent } from './task-deliveries.component';
import { TokenStorageService } from '../../../core/auth/token-storage.service';

describe('TaskDeliveriesComponent', () => {
  it('renders delivery rows and action labels', () => {
    TestBed.configureTestingModule({
      imports: [TaskDeliveriesComponent],
      providers: [
        provideRouter([]),
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '2' } } } },
        {
          provide: TasksService,
          useValue: {
            deliveries: () =>
              of({ items: [
                {
                  delivery: { id: 5, status: 'pending', delivered_at: null, grade: null },
                  student: {
                    id: 3,
                    first_name: 'Eva',
                    last_name: 'Solís',
                    enrollment_number: 'E3',
                  },
                },
              ], page: 1, per_page: 20, total: 1, total_pages: 1 }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TaskDeliveriesComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Eva Solís');
    expect(fixture.nativeElement.querySelector('[aria-label="Marcar entregada"]')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('[aria-label="Comentarios"]')).not.toBeNull();
    expect(
      fixture.nativeElement.querySelector('[aria-label="Notas del estudiante"]'),
    ).not.toBeNull();
  });
});
