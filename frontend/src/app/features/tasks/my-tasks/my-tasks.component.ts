import { Component, computed, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { TasksService, TaskDeliveryStatus, TaskRow } from '../tasks.service';
import { CyclesService } from '../../admin/cycles.service';
import { Page } from '../../../core/models/page';
import { BreadcrumbsComponent } from '../../../shared/breadcrumbs/breadcrumbs.component';
import { StatusFilterComponent } from '../../../shared/status-filter/status-filter.component';
import { PaginatorComponent } from '../../../shared/paginator/paginator.component';

@Component({
  selector: 'app-my-tasks',
  standalone: true,
  imports: [
    RouterLink,
    DatePipe,
    FormsModule,
    BreadcrumbsComponent,
    StatusFilterComponent,
    PaginatorComponent,
  ],
  templateUrl: './my-tasks.component.html',
  styleUrl: './my-tasks.component.scss',
})
export class MyTasksComponent {
  private readonly tasks = inject(TasksService);
  private readonly cyclesApi = inject(CyclesService);
  readonly statuses = signal<TaskDeliveryStatus[]>([]);
  readonly statusByCode = computed(
    () => new Map(this.statuses().map((status) => [status.code, status])),
  );
  selectedStatuses: string[] = [];
  search = '';
  rows = signal<Page<TaskRow>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
  loading = signal(true);
  cycles = signal<any[]>([]);
  cycleId = signal<number | null>(null);

  constructor() {
    this.tasks.deliveryStatuses().subscribe((statuses) => this.statuses.set(statuses));
    this.cyclesApi.all().subscribe((rows) => {
      this.cycles.set(rows.filter((row) => row.status === 'active'));
      if (this.cycles().length === 1) this.cycleId.set(Number(this.cycles()[0].id));
      this.load();
    });
  }

  load(page = 1): void {
    this.page.set(page);
    this.loading.set(true);
    this.tasks
      .myTasks(this.selectedStatuses, this.search, this.cycleId() ?? undefined, page)
      .subscribe({
        next: (response) => {
          this.rows.set(response);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });
  }

  filterChanged(): void {
    this.load(1);
  }

  toggleStatus(status: string): void {
    this.selectedStatuses = this.selectedStatuses.includes(status)
      ? this.selectedStatuses.filter((value) => value !== status)
      : [...this.selectedStatuses, status];
    this.filterChanged();
  }
}
