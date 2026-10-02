import { Component, inject } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { TasksService, Comment } from '../tasks.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-delivery-comments',
  standalone: true,
  imports: [DatePipe, FormsModule],
  templateUrl: './delivery-comments.component.html',
  styleUrl: './delivery-comments.component.scss',
})
export class DeliveryCommentsComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  private readonly toast = inject(ToastService);
  readonly deliveryId = Number(this.route.snapshot.paramMap.get('deliveryId'));
  comments: Comment[] = [];
  body = '';

  constructor() {
    this.load();
  }

  load(): void {
    this.tasks.comments(this.deliveryId).subscribe((page) => (this.comments = page.data));
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
