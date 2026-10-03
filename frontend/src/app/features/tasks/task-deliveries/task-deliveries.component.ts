import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TasksService, DeliveryRow } from '../tasks.service';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';
import { ToastService } from '../../../core/services/toast.service';
import { TokenStorageService } from '../../../core/auth/token-storage.service';
import { StudentNotesModalComponent } from '../student-notes-modal/student-notes-modal.component';
import { Page } from '../../../core/models/page';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ColumnComponent } from '../../../shared/data-table/column.component';

@Component({
  selector: 'app-task-deliveries',
  standalone: true,
  imports: [DatePipe, RouterLink, IconButtonComponent, StudentNotesModalComponent, DataTableComponent, ColumnComponent],
  templateUrl: './task-deliveries.component.html',
  styleUrl: './task-deliveries.component.scss',
})
export class TaskDeliveriesComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  private readonly toast = inject(ToastService);
  readonly isAdmin = inject(TokenStorageService).getRole() === 'admin';
  readonly taskId = Number(this.route.snapshot.paramMap.get('taskId'));
  deliveries = signal<Page<DeliveryRow>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
  notesStudentId = signal<number | null>(null);

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
    this.load();
  }

  load(page = this.page()): void {
    this.page.set(page);
    this.tasks.deliveries(this.taskId, page).subscribe((rows) => this.deliveries.set(rows));
  }

  markDelivered(deliveryId: number): void {
    this.tasks.markDelivered(deliveryId).subscribe({
      next: () => this.load(),
      error: () => this.toast.show('No se pudo marcar la entrega.'),
    });
  }

  grade(deliveryId: number): void {
    const value = prompt('Calificación (0 a 100)');
    if (value === null || value.trim() === '') return;
    const grade = Number(value);
    if (!Number.isFinite(grade) || grade < 0 || grade > 100) {
      this.toast.show('La calificación debe estar entre 0 y 100.');
      return;
    }
    this.tasks.grade(deliveryId, grade).subscribe({
      next: () => this.load(),
      error: () => this.toast.show('No se pudo guardar la calificación.'),
    });
  }
}
