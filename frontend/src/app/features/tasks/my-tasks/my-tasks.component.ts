import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { TasksService, TaskRow } from '../tasks.service';
import { CyclesService } from '../../admin/cycles.service';

@Component({
  selector: 'app-my-tasks',
  standalone: true,
  imports: [RouterLink, DatePipe, FormsModule],
  templateUrl: './my-tasks.component.html',
  styleUrl: './my-tasks.component.scss',
})
export class MyTasksComponent {
  private readonly tasks = inject(TasksService);
  private readonly cyclesApi = inject(CyclesService);
  readonly filters = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'delivered', label: 'Entregada' },
    { value: 'graded', label: 'Calificada' },
    { value: 'cancelled', label: 'Cancelada' },
  ];
  status = 'pending';
  rows = signal<TaskRow[]>([]);
  loading = signal(true);
  cycles = signal<any[]>([]);
  cycleId = signal<number | null>(null);

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
    this.cyclesApi.list().subscribe((rows) => {
      this.cycles.set(rows.filter((row) => row.status === 'active'));
      if (this.cycles().length === 1) this.cycleId.set(Number(this.cycles()[0].id));
      this.load();
    });
  }

  load(): void {
    this.loading.set(true);
    this.tasks.myTasks(this.status, this.cycleId() ?? undefined).subscribe({
      next: (response) => {
        this.rows.set(response.body ?? []);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
