import { Component, HostListener, inject, input, output, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgTemplateOutlet } from '@angular/common';
import { TasksService, Comment, DeliveryHistoryEvent } from '../tasks.service';
import { Page } from '../../../core/models/page';
import { PaginatorComponent } from '../../../shared/paginator/paginator.component';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-delivery-comments',
  standalone: true,
  imports: [DatePipe, FormsModule, PaginatorComponent, NgTemplateOutlet],
  templateUrl: './delivery-comments.component.html',
  styleUrl: './delivery-comments.component.scss',
})
export class DeliveryCommentsComponent {
  private readonly tasks = inject(TasksService);
  private readonly toast = inject(ToastService);
  deliveryId = input.required<number>();
  embedded = input(false);
  closed = output<void>();
  comments = signal<Page<Comment>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
  busy = signal(false);
  historyRefresh = output<DeliveryHistoryEvent[]>();
  body = '';

  ngOnInit(): void {
    this.tasks.markCommentsRead(this.deliveryId()).subscribe({
      next: () => this.load(),
      error: () => this.load(),
    });
  }

  load(page = this.page()): void {
    this.page.set(page);
    this.tasks.comments(this.deliveryId(), page).subscribe((rows) => this.comments.set(rows));
  }

  submit(): void {
    this.addComment(this.body);
  }

  promptComment(): void {
    const body = window.prompt('Escribe tu comentario');
    if (body === null) return;
    this.addComment(body);
  }

  private addComment(value: string): void {
    const body = value.trim();
    if (!body) return;
    if (body.length > 2000) {
      this.toast.show('El comentario no puede exceder 2000 caracteres.');
      return;
    }
    this.busy.set(true);
    this.tasks.addComment(this.deliveryId(), body).subscribe({
      next: () => {
        this.busy.set(false);
        this.body = '';
        this.load();
        this.tasks.deliveryHistory(this.deliveryId()).subscribe({
          next: (response) => this.historyRefresh.emit(response.items),
          error: () => this.toast.show('No se pudo cargar la bitácora.'),
        });
      },
      error: () => {
        this.busy.set(false);
        this.toast.show('No se pudo enviar el comentario.');
      },
    });
  }

  error = signal('');

  @HostListener('document:keydown.escape')
  closeOnEscape(): void {
    if (!this.embedded()) this.closed.emit();
  }
}
