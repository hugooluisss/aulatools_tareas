import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { TasksService, TaskDetail, DeliveryHistoryEvent } from '../tasks.service';
import { TaskDeliveryStatus } from '../tasks.service';
import { BreadcrumbsComponent } from '../../../shared/breadcrumbs/breadcrumbs.component';
import { DeliveryCommentsComponent } from '../delivery-comments/delivery-comments.component';
import { StatusBadgeComponent } from '../../../shared/status-badge/status-badge.component';

@Component({
  selector: 'app-task-detail',
  standalone: true,
  imports: [DatePipe, BreadcrumbsComponent, DeliveryCommentsComponent, StatusBadgeComponent],
  templateUrl: './task-detail.component.html',
  styleUrl: './task-detail.component.scss',
})
export class TaskDetailComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  detail = signal<TaskDetail | undefined>(undefined);
  history = signal<DeliveryHistoryEvent[]>([]);
  historyError = signal(false);
  statuses = signal<TaskDeliveryStatus[]>([]);

  constructor() {
    const deliveryId = Number(this.route.snapshot.paramMap.get('deliveryId'));
    this.tasks.myTask(deliveryId).subscribe((detail) => this.detail.set(detail));
    this.tasks.deliveryHistory(deliveryId).subscribe({
      next: (response) => this.history.set(response.items),
      error: () => this.historyError.set(true),
    });
    this.tasks.deliveryStatuses().subscribe((statuses) => this.statuses.set(statuses));
  }

  eventMessage(event: DeliveryHistoryEvent): string {
    const payload = event.payload ?? {};
    const previousStatus = payload['old_status'] ?? payload['from_status'];
    const nextStatus = payload['new_status'] ?? payload['to_status'];
    const previousGrade = payload['old_grade'];
    const nextGrade = payload['new_grade'] ?? payload['grade'];
    const commentId = payload['comment_id'];
    const actor = event.actor?.name ?? 'Sistema';
    const statusName = (value: unknown) =>
      this.statuses().find((item) => item.code === value)?.label ?? value ?? 'desconocido';
    switch (event.type) {
      case 'task_created':
        return `${actor} asignó la tarea.`;
      case 'task_updated':
        return `${actor} actualizó la tarea.`;
      case 'comment_added':
        return `${actor} comentó${commentId ? ` (comentario ${commentId})` : ''}.`;
      case 'delivered':
        return `${actor} entregó la tarea.`;
      case 'undelivered':
        return `${actor} revirtió la entrega.`;
      case 'graded':
        return `${actor} calificó: ${nextGrade ?? 'sin calificación'}.`;
      case 'regrade':
        return `${actor} recalificó: ${previousGrade ?? '—'} → ${nextGrade ?? '—'}.`;
      case 'status_changed':
        return `${actor} cambió el estado de ${statusName(previousStatus)} a ${statusName(nextStatus)}.`;
      default:
        return `${actor} registró un cambio.`;
    }
  }

  statusFor(code: string): TaskDeliveryStatus | undefined {
    return this.statuses().find((status) => status.code === code);
  }
}
