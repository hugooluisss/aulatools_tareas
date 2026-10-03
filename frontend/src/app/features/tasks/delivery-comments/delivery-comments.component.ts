import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { TasksService, Comment } from '../tasks.service';
import { ToastService } from '../../../core/services/toast.service';
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
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  private readonly toast = inject(ToastService);
  readonly deliveryId = Number(this.route.snapshot.paramMap.get('deliveryId'));
  comments = signal<Page<Comment>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
  body = '';

  constructor() {
    this.load();
  }

  load(page = this.page()): void {
    this.page.set(page);
    this.tasks.comments(this.deliveryId, page).subscribe((rows) => this.comments.set(rows));
  }

  submit(): void {
    const body = this.body.trim();
    if (!body || body.length > 2000) {
      this.toast.show('Escribe un comentario de hasta 2000 caracteres.');
      return;
    }
    this.tasks.addComment(this.deliveryId, body).subscribe({
      next: () => {
        this.body = '';
        this.load();
      },
      error: () => this.toast.show('No se pudo enviar el comentario.'),
    });
  }
}
