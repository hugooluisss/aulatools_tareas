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
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: { get: () => '2' },
              queryParamMap: { get: () => null },
            },
          },
        },
        {
          provide: TasksService,
          useValue: {
            deliveries: () =>
              of({
                items: [
                  {
                    delivery: {
                      id: 5,
                      status: 'pending',
                      delivered_at: null,
                      grade: null,
                      unread_comments: 3,
                    },
                    student: {
                      id: 3,
                      first_name: 'Eva',
                      last_name: 'Solís',
                      enrollment_number: 'E3',
                    },
                  },
                ],
                page: 1,
                per_page: 20,
                total: 1,
                total_pages: 1,
              }),
            deliveryStatuses: () =>
              of([
                { code: 'pending', label: 'Pendiente', color: '#FFF3CD', text_color: '#664D03' },
              ]),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(TaskDeliveriesComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Eva Solís');
    expect(fixture.nativeElement.querySelector('input[type="search"]').placeholder).toBe(
      'Buscar estudiante',
    );
    expect(fixture.nativeElement.querySelector('.status-badge').textContent).toContain('Pendiente');
    expect(fixture.nativeElement.querySelector('.status-filter__button').textContent).toContain(
      'Pendiente',
    );
    expect(
      fixture.nativeElement.querySelector('[aria-label="Comentarios"] .icon-button__badge')
        .textContent,
    ).toContain('3');
    const deliveryHeaders = fixture.nativeElement.querySelectorAll('app-data-table th');
    expect(deliveryHeaders[0].textContent).toContain('Matrícula');
    expect(deliveryHeaders[1].textContent).toContain('Estudiante');
    expect(fixture.nativeElement.querySelector('[aria-label="Marcar entregada"]')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('[aria-label="Comentarios"]')).not.toBeNull();
    expect(
      fixture.nativeElement.querySelector('[aria-label="Notas del estudiante"]'),
    ).not.toBeNull();
  });

  it('sends filters and returns to page one when they change', () => {
    const deliveries = vi.fn(() =>
      of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 }),
    );
    TestBed.configureTestingModule({
      imports: [TaskDeliveriesComponent],
      providers: [
        provideRouter([]),
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: { paramMap: { get: () => '2' }, queryParamMap: { get: () => null } },
          },
        },
        { provide: TasksService, useValue: { deliveries, deliveryStatuses: () => of([]) } },
      ],
    });
    const fixture = TestBed.createComponent(TaskDeliveriesComponent);
    fixture.detectChanges();
    fixture.componentInstance.load(3);
    fixture.componentInstance.search = ' Ana ';
    fixture.componentInstance.toggleStatus('graded');

    expect(deliveries).toHaveBeenLastCalledWith(2, 1, ' Ana ', ['graded']);
    expect(fixture.componentInstance.page()).toBe(1);
  });

  it('undelivers a delivered delivery and reloads', () => {
    const markUndelivered = vi.fn(() => of({}));
    const deliveries = vi.fn(() =>
      of({
        items: [
          {
            delivery: {
              id: 5,
              status: 'delivered',
              delivered_at: '2026-10-01 12:00:00',
              grade: null,
              unread_comments: 0,
            },
            student: { id: 3, first_name: 'Eva', last_name: 'Solís', enrollment_number: 'E3' },
          },
        ],
        page: 1,
        per_page: 20,
        total: 1,
        total_pages: 1,
      }),
    );
    TestBed.configureTestingModule({
      imports: [TaskDeliveriesComponent],
      providers: [
        provideRouter([]),
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: { paramMap: { get: () => '2' }, queryParamMap: { get: () => null } },
          },
        },
        {
          provide: TasksService,
          useValue: { deliveries, markUndelivered, deliveryStatuses: () => of([]) },
        },
      ],
    });
    const fixture = TestBed.createComponent(TaskDeliveriesComponent);
    fixture.detectChanges();

    fixture.nativeElement.querySelector('[aria-label="Desentregar"]').click();

    expect(markUndelivered).toHaveBeenCalledWith(5);
    expect(deliveries).toHaveBeenCalledTimes(2);
  });

  it('saves the typed grade from the grading modal', async () => {
    const grade = vi.fn(() => of({}));
    const deliveries = vi.fn(() =>
      of({
        items: [
          {
            delivery: {
              id: 5,
              status: 'delivered',
              delivered_at: '2026-10-01 12:00:00',
              grade: null,
              unread_comments: 0,
            },
            student: { id: 3, first_name: 'Eva', last_name: 'Solís', enrollment_number: 'E3' },
          },
        ],
        page: 1,
        per_page: 20,
        total: 1,
        total_pages: 1,
      }),
    );
    TestBed.configureTestingModule({
      imports: [TaskDeliveriesComponent],
      providers: [
        provideRouter([]),
        { provide: TokenStorageService, useValue: { getRole: () => 'teacher' } },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: { paramMap: { get: () => '2' }, queryParamMap: { get: () => null } },
          },
        },
        {
          provide: TasksService,
          useValue: { deliveries, grade, deliveryStatuses: () => of([]) },
        },
      ],
    });
    const fixture = TestBed.createComponent(TaskDeliveriesComponent);
    fixture.detectChanges();
    fixture.nativeElement.querySelector('[aria-label="Calificar"]').click();
    fixture.detectChanges();
    await fixture.whenStable();

    const input = fixture.nativeElement.querySelector('#delivery-grade');
    input.value = '85';
    input.dispatchEvent(new Event('input'));
    fixture.detectChanges();
    fixture.nativeElement.querySelector('form').dispatchEvent(new Event('submit'));

    expect(grade).toHaveBeenCalledWith(5, 85);
  });
});
