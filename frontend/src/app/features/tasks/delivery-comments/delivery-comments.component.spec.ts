import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { DeliveryCommentsComponent } from './delivery-comments.component';

describe('DeliveryCommentsComponent', () => {
  it('renders the private thread and reply form', () => {
    TestBed.configureTestingModule({
      imports: [DeliveryCommentsComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '5' } } } },
        {
          provide: TasksService,
          useValue: {
            comments: () =>
              of({
                data: [
                  {
                    id: 1,
                    author: { first_name: 'Ana', last_name: 'Paz', role: 'teacher' },
                    body: 'Buen trabajo',
                    created_at: '2026-09-30T12:00:00Z',
                  },
                ],
              }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(DeliveryCommentsComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Buen trabajo');
    expect(fixture.nativeElement.textContent).toContain('Enviar');
  });
});
