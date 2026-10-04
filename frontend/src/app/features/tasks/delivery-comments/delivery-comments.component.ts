import { Component, HostListener, inject, input, output, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TasksService, Comment } from '../tasks.service';
import { Page } from '../../../core/models/page';
import { PaginatorComponent } from '../../../shared/paginator/paginator.component';

@Component({
  selector: 'app-delivery-comments',
  standalone: true,
  imports: [DatePipe, FormsModule, PaginatorComponent],
  templateUrl: './delivery-comments.component.html',
  styleUrl: './delivery-comments.component.scss',
})
export class DeliveryCommentsComponent {
  private readonly tasks = inject(TasksService);
  deliveryId = input.required<number>();
  closed = output<void>();
  comments = signal<Page<Comment>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
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
    const body = this.body.trim();
    if (!body || body.length > 2000) return;
    this.tasks.addComment(this.deliveryId(), body).subscribe({
      next: () => {
        this.body = '';
        this.load();
      },
      error: () => this.error.set('No se pudo enviar el comentario.'),
    });
  }

  error = signal('');

  @HostListener('document:keydown.escape')
  closeOnEscape(): void {
    this.closed.emit();
  }
}
