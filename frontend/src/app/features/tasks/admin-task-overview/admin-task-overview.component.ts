import { Component, computed, HostListener, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Page } from '../../../core/models/page';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ColumnComponent } from '../../../shared/data-table/column.component';
import { TaskDeliveryStatus, TaskOverviewRow, TasksService } from '../tasks.service';

@Component({
  selector: 'app-admin-task-overview',
  standalone: true,
  imports: [DatePipe, FormsModule, DataTableComponent, ColumnComponent],
  templateUrl: './admin-task-overview.component.html',
  styleUrl: './admin-task-overview.component.scss',
})
export class AdminTaskOverviewComponent {
  private readonly tasks = inject(TasksService);
  readonly statuses = signal<TaskDeliveryStatus[]>([]);
  readonly statusByCode = computed(
    () => new Map(this.statuses().map((status) => [status.code, status])),
  );
  readonly rows = signal<TaskOverviewRow[]>([]);
  readonly page = signal(1);
  readonly tablePage = computed<Page<TaskOverviewRow>>(() => ({
    items: this.rows().slice((this.page() - 1) * 20, this.page() * 20),
    page: this.page(),
    per_page: 20,
    total: this.rows().length,
    total_pages: Math.max(1, Math.ceil(this.rows().length / 20)),
  }));
  search = '';
  selectedStatuses: string[] = [];
  loading = signal(false);
  selectedRow = signal<TaskOverviewRow | null>(null);

  constructor() {
    this.tasks.deliveryStatuses().subscribe((statuses) => this.statuses.set(statuses));
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.tasks.overview(this.search, this.selectedStatuses).subscribe({
      next: (rows) => this.rows.set(rows),
      complete: () => this.loading.set(false),
    });
  }

  loadPage(page: number): void {
    this.page.set(page);
  }

  filtersChanged(): void {
    this.page.set(1);
    this.load();
  }

  toggleStatus(status: string): void {
    this.selectedStatuses = this.selectedStatuses.includes(status)
      ? this.selectedStatuses.filter((value) => value !== status)
      : [...this.selectedStatuses, status];
    this.filtersChanged();
  }

  openDetails(row: TaskOverviewRow): void {
    this.selectedRow.set(row);
  }

  closeDetails(): void {
    this.selectedRow.set(null);
  }

  @HostListener('document:keydown.escape')
  closeDetailsOnEscape(): void {
    if (this.selectedRow()) this.closeDetails();
  }
}
