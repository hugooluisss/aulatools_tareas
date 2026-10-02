import { Component, inject } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TasksService, Student, Task } from '../tasks.service';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';

@Component({
  selector: 'app-teacher-subject-detail',
  standalone: true,
  imports: [RouterLink, IconButtonComponent, DatePipe],
  templateUrl: './teacher-subject-detail.component.html',
  styleUrl: './teacher-subject-detail.component.scss',
})
export class TeacherSubjectDetailComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  readonly subjectId = Number(this.route.snapshot.paramMap.get('subjectId'));
  students: Student[] = [];
  tasksList: Task[] = [];

  statusLabel(status: string): string {
    return { active: 'Activa', cancelled: 'Cancelada' }[status] ?? status;
  }

  constructor() {
    this.tasks.students(this.subjectId).subscribe((page) => (this.students = page.data));
    this.loadTasks();
  }

  loadTasks(): void {
    this.tasks.tasks(this.subjectId).subscribe((page) => (this.tasksList = page.data));
  }

  createTask(): void {
    const name = prompt('Nombre de la tarea');
    if (!name?.trim()) return;
    const description = prompt('Descripción') ?? '';
    const dueAt = prompt('Fecha y hora de vencimiento (ISO 8601)');
    if (!dueAt) return;
    this.tasks
      .createTask(this.subjectId, { name: name.trim(), description, due_at: dueAt })
      .subscribe(() => this.loadTasks());
  }

  editTask(task: Task): void {
    const name = prompt('Nombre de la tarea', task.name);
    if (!name?.trim()) return;
    const description = prompt('Descripción', task.description) ?? '';
    const dueAt = prompt('Fecha y hora de vencimiento (ISO 8601)', task.due_at);
    if (!dueAt) return;
    this.tasks
      .updateTask(task.id, { name: name.trim(), description, due_at: dueAt })
      .subscribe(() => this.loadTasks());
  }

  cancelTask(task: Task): void {
    if (confirm(`¿Cancelar la tarea «${task.name}»?`)) {
      this.tasks.cancelTask(task.id).subscribe(() => this.loadTasks());
    }
  }
}
