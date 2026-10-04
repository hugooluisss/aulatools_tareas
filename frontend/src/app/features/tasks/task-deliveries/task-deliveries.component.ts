import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { TaskDeliveryStatus, TasksService, DeliveryRow } from '../tasks.service';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';
import { ToastService } from '../../../core/services/toast.service';
import { TokenStorageService } from '../../../core/auth/token-storage.service';
import { StudentNotesModalComponent } from '../student-notes-modal/student-notes-modal.component';
import { Page } from '../../../core/models/page';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ColumnComponent } from '../../../shared/data-table/column.component';
import { BreadcrumbsComponent } from '../../../shared/breadcrumbs/breadcrumbs.component';
import { DeliveryCommentsComponent } from '../delivery-comments/delivery-comments.component';
import { FormsModule } from '@angular/forms';
import { StatusBadgeComponent } from '../../../shared/status-badge/status-badge.component';
import { StatusFilterComponent } from '../../../shared/status-filter/status-filter.component';

@Component({
  selector: 'app-task-deliveries',
  standalone: true,
  imports: [
    DatePipe,
    FormsModule,
    IconButtonComponent,
    StudentNotesModalComponent,
    DataTableComponent,
    ColumnComponent,
    BreadcrumbsComponent,
    DeliveryCommentsComponent,
    StatusBadgeComponent,
    StatusFilterComponent,
  ],
  templateUrl: './task-deliveries.component.html',
  styleUrl: './task-deliveries.component.scss',
})
export class TaskDeliveriesComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  private readonly toast = inject(ToastService);
  readonly isAdmin = inject(TokenStorageService).getRole() === 'admin';
  readonly taskId = Number(this.route.snapshot.paramMap.get('taskId'));
  readonly subjectId = Number(this.route.snapshot.queryParamMap.get('subjectId'));
  readonly subjectName = this.route.snapshot.queryParamMap.get('subjectName') ?? 'Materia';
  readonly taskName = this.route.snapshot.queryParamMap.get('taskName') ?? 'Tarea';
  deliveries = signal<Page<DeliveryRow>>({
    items: [],
    page: 1,
    per_page: 20,
    total: 0,
    total_pages: 1,
  });
  page = signal(1);
  notesStudentId = signal<number | null>(null);
  commentsDeliveryId = signal<number | null>(null);
  gradingDeliveryId = signal<number | null>(null);
  gradeValue: number | null = null;
  grading = signal(false);
  gradeError = signal('');
  readonly statuses = signal<TaskDeliveryStatus[]>([]);
  readonly statusByCode = new Map<string, TaskDeliveryStatus>();
  search = '';
  selectedStatuses: string[] = [];

  constructor() {
    this.tasks.deliveryStatuses().subscribe((statuses) => {
      this.statuses.set(statuses);
      this.statusByCode.clear();
      statuses.forEach((status) => this.statusByCode.set(status.code, status));
    });
    this.load();
  }

  load(page = this.page()): void {
    this.page.set(page);
    this.tasks
      .deliveries(this.taskId, page, this.search, this.selectedStatuses)
      .subscribe((rows) => this.deliveries.set(rows));
  }

  filtersChanged(): void {
    this.load(1);
  }

  toggleStatus(status: string): void {
    this.selectedStatuses = this.selectedStatuses.includes(status)
      ? this.selectedStatuses.filter((value) => value !== status)
      : [...this.selectedStatuses, status];
    this.filtersChanged();
  }

  markDelivered(deliveryId: number): void {
    this.tasks.markDelivered(deliveryId).subscribe({
      next: () => this.load(),
      error: () => this.toast.show('No se pudo marcar la entrega.'),
    });
  }

  markUndelivered(deliveryId: number): void {
    this.tasks.markUndelivered(deliveryId).subscribe({
      next: () => this.load(),
      error: () => this.toast.show('No se pudo desentregar la tarea.'),
    });
  }

  saveGrade(): void {
    const grade = this.gradeValue;
    if (grade === null || !Number.isFinite(grade) || grade < 0 || grade > 100) {
      this.gradeError.set('La calificación debe estar entre 0 y 100.');
      return;
    }
    const deliveryId = this.gradingDeliveryId();
    if (deliveryId === null || this.grading()) return;
    this.grading.set(true);
    this.tasks.grade(deliveryId, grade).subscribe({
      next: () => {
        this.grading.set(false);
        this.gradingDeliveryId.set(null);
        this.load();
      },
      error: () => {
        this.gradeError.set('No se pudo guardar la calificación.');
        this.grading.set(false);
      },
    });
  }

  openGrade(deliveryId: number): void {
    this.gradeValue = null;
    this.gradeError.set('');
    this.gradingDeliveryId.set(deliveryId);
  }

  refreshAfterComments(): void {
    this.commentsDeliveryId.set(null);
    this.load();
  }
}
