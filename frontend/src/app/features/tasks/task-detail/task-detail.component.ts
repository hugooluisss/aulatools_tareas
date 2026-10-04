import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { TasksService, TaskDetail } from '../tasks.service';
import { BreadcrumbsComponent } from '../../../shared/breadcrumbs/breadcrumbs.component';
import { DeliveryCommentsComponent } from '../delivery-comments/delivery-comments.component';

@Component({
  selector: 'app-task-detail',
  standalone: true,
  imports: [DatePipe, BreadcrumbsComponent, DeliveryCommentsComponent],
  templateUrl: './task-detail.component.html',
  styleUrl: './task-detail.component.scss',
})
export class TaskDetailComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  detail = signal<TaskDetail | undefined>(undefined);
  commentsOpen = signal(false);

  statusLabel(status: string): string {
    return (
      {
        pending: 'Pendiente',
        delivered: 'Entregada',
        graded: 'Calificada',
        cancelled: 'Cancelada',
      }[status] ?? status
    );
  }

  constructor() {
    const deliveryId = Number(this.route.snapshot.paramMap.get('deliveryId'));
    this.tasks.myTask(deliveryId).subscribe((detail) => this.detail.set(detail));
  }
}
