import { Component, inject } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { TasksService, TaskRow } from '../tasks.service';

@Component({
  selector: 'app-my-tasks',
  standalone: true,
  imports: [RouterLink, DatePipe, FormsModule],
  templateUrl: './my-tasks.component.html',
  styleUrl: './my-tasks.component.scss',
})
export class MyTasksComponent {
  private readonly tasks = inject(TasksService);
  readonly filters = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'delivered', label: 'Entregada' },
    { value: 'graded', label: 'Calificada' },
    { value: 'cancelled', label: 'Cancelada' },
  ];
  status = 'pending';
  rows: TaskRow[] = [];
  loading = true;

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

  load(): void {
    this.loading = true;
    this.tasks.myTasks(this.status).subscribe({
      next: (page) => {
        this.rows = page.data;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }
}
