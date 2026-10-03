import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TasksService, Subject } from '../tasks.service';
import { CyclesService } from '../../admin/cycles.service';

@Component({
  selector: 'app-teacher-subjects',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './teacher-subjects.component.html',
  styleUrl: './teacher-subjects.component.scss',
})
export class TeacherSubjectsComponent {
  private readonly tasks = inject(TasksService);
  private readonly cyclesApi = inject(CyclesService);
  subjects = signal<Subject[]>([]);
  cycles = signal<any[]>([]);
  cycleId = signal<number | null>(null);

  constructor() {
    this.cyclesApi.list().subscribe((rows) => {
      this.cycles.set(rows.filter((row) => row.status === 'active'));
      if (this.cycles().length === 1) this.cycleId.set(Number(this.cycles()[0].id));
      this.load();
    });
  }
  load(): void {
    if (this.cycles().length > 1 && !this.cycleId()) return;
    this.tasks.subjects(this.cycleId() ?? undefined).subscribe((rows) => this.subjects.set(rows));
  }
}
