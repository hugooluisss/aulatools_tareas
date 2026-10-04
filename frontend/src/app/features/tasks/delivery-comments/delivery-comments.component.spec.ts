import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { TasksService } from '../tasks.service';
import { DeliveryCommentsComponent } from './delivery-comments.component';
import { TokenStorageService } from '../../../core/auth/token-storage.service';
import { ToastService } from '../../../core/services/toast.service';

describe('DeliveryCommentsComponent', () => {
  it('renders the private thread and reply form in modal mode', () => {
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
            addComment: () => of({}),
            deliveryHistory: () => of({ items: [] }),
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

  it('renders embedded threads without modal chrome or inline form', () => {
    TestBed.configureTestingModule({
      imports: [DeliveryCommentsComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '5' } } } },
        { provide: TokenStorageService, useValue: { getRole: () => 'student' } },
        {
          provide: TasksService,
          useValue: {
            markCommentsRead: () => of({}),
            comments: () => of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 }),
            addComment: () => of({}),
            deliveryHistory: () => of({ items: [] }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(DeliveryCommentsComponent);
    fixture.componentRef.setInput('deliveryId', 5);
    fixture.componentRef.setInput('embedded', true);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[role="dialog"]')).toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Aún no hay comentarios.');
    expect(fixture.nativeElement.textContent).toContain('Agregar comentario');
    expect(fixture.nativeElement.querySelector('form')).toBeNull();
  });

  it('prompts, trims, adds a comment, and refreshes comments and history', () => {
    const prompt = vi.spyOn(window, 'prompt').mockReturnValue('  Mi comentario  ');
    const addComment = vi.fn().mockReturnValue(of({}));
    const comments = vi
      .fn()
      .mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 }));
    const deliveryHistory = vi.fn().mockReturnValue(of({ items: [] }));
    TestBed.configureTestingModule({
      imports: [DeliveryCommentsComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '5' } } } },
        { provide: TokenStorageService, useValue: { getRole: () => 'student' } },
        {
          provide: TasksService,
          useValue: {
            markCommentsRead: () => of({}),
            comments,
            addComment,
            deliveryHistory,
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(DeliveryCommentsComponent);
    fixture.componentRef.setInput('deliveryId', 5);
    fixture.componentRef.setInput('embedded', true);
    fixture.detectChanges();
    fixture.nativeElement.querySelector('button').click();
    expect(prompt).toHaveBeenCalledWith('Escribe tu comentario');
    expect(addComment).toHaveBeenCalledWith(5, 'Mi comentario');
    expect(comments).toHaveBeenCalledTimes(2);
    expect(deliveryHistory).toHaveBeenCalledWith(5);
    prompt.mockRestore();
  });

  it('ignores cancelled, empty and oversized prompt values', () => {
    const prompt = vi.spyOn(window, 'prompt');
    const addComment = vi.fn().mockReturnValue(of({}));
    const toast = { show: vi.fn() };
    TestBed.configureTestingModule({
      imports: [DeliveryCommentsComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '5' } } } },
        { provide: TokenStorageService, useValue: { getRole: () => 'student' } },
        { provide: ToastService, useValue: toast },
        {
          provide: TasksService,
          useValue: {
            markCommentsRead: () => of({}),
            comments: () => of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 }),
            addComment,
            deliveryHistory: () => of({ items: [] }),
          },
        },
      ],
    });
    const fixture = TestBed.createComponent(DeliveryCommentsComponent);
    fixture.componentRef.setInput('deliveryId', 5);
    fixture.componentRef.setInput('embedded', true);
    fixture.detectChanges();
    const button = fixture.nativeElement.querySelector('button');
    prompt.mockReturnValueOnce(null);
    button.click();
    prompt.mockReturnValueOnce('   ');
    button.click();
    prompt.mockReturnValueOnce('x'.repeat(2001));
    button.click();
    expect(addComment).not.toHaveBeenCalled();
    expect(toast.show).toHaveBeenCalledWith('El comentario no puede exceder 2000 caracteres.');
    prompt.mockRestore();
  });
});
