import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { DeliveryCommentsComponent } from './delivery-comments.component';
import { TokenStorageService } from '../../../core/auth/token-storage.service';

describe('DeliveryCommentsComponent', () => {
  it('renders the private thread and reply form', () => {
    const markCommentsRead = vi.fn().mockReturnValue(of({}));
    TestBed.configureTestingModule({
      imports: [DeliveryCommentsComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '5' } } } },
        { provide: TokenStorageService, useValue: { getRole: () => 'admin' } },
        {
          provide: TasksService,
          useValue: {
            markCommentsRead,
            comments: () =>
              of({
                items: [
                  {
                    id: 1,
                    author: { first_name: 'Ana', last_name: 'Paz', role: 'teacher' },
                    body: 'Buen trabajo',
                    created_at: '2026-09-30T12:00:00Z',
                  },
                ],
                page: 1,
                per_page: 20,
                total: 1,
                total_pages: 1,
              }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(DeliveryCommentsComponent);
    fixture.componentRef.setInput('deliveryId', 5);
    fixture.detectChanges();
    expect(markCommentsRead).toHaveBeenCalledWith(5);
    expect(
      fixture.nativeElement.querySelector('[role="dialog"][aria-modal="true"]'),
    ).not.toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Buen trabajo');
    expect(fixture.nativeElement.textContent).toContain('Enviar');
    expect(fixture.nativeElement.querySelector('form')).not.toBeNull();
  });
});
