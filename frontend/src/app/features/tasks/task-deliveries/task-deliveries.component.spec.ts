import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { TaskDeliveriesComponent } from './task-deliveries.component';

describe('TaskDeliveriesComponent', () => {
  it('renders delivery rows and action labels', () => {
    TestBed.configureTestingModule({
      imports: [TaskDeliveriesComponent],
      providers: [
        provideRouter([]),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '2' } } } },
        {
          provide: TasksService,
          useValue: {
            deliveries: () =>
              of({
                data: [
                  {
                    delivery: { id: 5, status: 'pending', delivered_at: null, grade: null },
                    student: {
                      id: 3,
                      first_name: 'Eva',
                      last_name: 'Solís',
                      enrollment_number: 'E3',
                    },
                  },
                ],
              }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TaskDeliveriesComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Eva Solís');
    expect(fixture.nativeElement.querySelector('[aria-label="Marcar entregada"]')).not.toBeNull();
  });
});
