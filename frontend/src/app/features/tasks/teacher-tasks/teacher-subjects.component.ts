import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TasksService, Subject } from '../tasks.service';
import { CyclesService } from '../../admin/cycles.service';
import { TokenStorageService } from '../../../core/auth/token-storage.service';
import { Page } from '../../../core/models/page';
import { PaginatorComponent } from '../../../shared/paginator/paginator.component';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ColumnComponent } from '../../../shared/data-table/column.component';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';

@Component({
  selector: 'app-teacher-subjects',
  standalone: true,
  imports: [RouterLink, PaginatorComponent, DataTableComponent, ColumnComponent, IconButtonComponent],
  templateUrl: './teacher-subjects.component.html',
  styleUrl: './teacher-subjects.component.scss',
})
export class TeacherSubjectsComponent {
  private readonly tasks = inject(TasksService);
  private readonly cyclesApi = inject(CyclesService);
  readonly isAdmin = inject(TokenStorageService).getRole() === 'admin';
  subjects = signal<Page<Subject>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
  cycles = signal<any[]>([]);
  cycleId = signal<number | null>(null);

  constructor() {
    this.cyclesApi.all().subscribe((rows) => {
      this.cycles.set(rows.filter((row) => row.status === 'active'));
      if (this.cycles().length === 1) this.cycleId.set(Number(this.cycles()[0].id));
      this.load();
    });
  }
  load(): void {
    if (this.cycles().length > 1 && !this.cycleId()) return;
    this.tasks.subjects(this.cycleId() ?? undefined, this.page()).subscribe((rows) => this.subjects.set(rows));
  }

  loadPage(page: number): void { this.page.set(page); this.load(); }
  filterChanged(): void { this.page.set(1); this.load(); }
}
