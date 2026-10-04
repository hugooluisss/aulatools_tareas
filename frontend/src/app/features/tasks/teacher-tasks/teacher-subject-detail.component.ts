import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TasksService, Student, Task } from '../tasks.service';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';
import { CyclesService } from '../../admin/cycles.service';
import { TokenStorageService } from '../../../core/auth/token-storage.service';
import { Page } from '../../../core/models/page';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ColumnComponent } from '../../../shared/data-table/column.component';
import { BreadcrumbsComponent } from '../../../shared/breadcrumbs/breadcrumbs.component';

@Component({
  selector: 'app-teacher-subject-detail',
  standalone: true,
  imports: [
    RouterLink,
    IconButtonComponent,
    DatePipe,
    FormsModule,
    DataTableComponent,
    ColumnComponent,
    BreadcrumbsComponent,
  ],
  templateUrl: './teacher-subject-detail.component.html',
  styleUrl: './teacher-subject-detail.component.scss',
})
export class TeacherSubjectDetailComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly tasks = inject(TasksService);
  private readonly cyclesApi = inject(CyclesService);
  readonly isAdmin = inject(TokenStorageService).getRole() === 'admin';
  readonly subjectId = Number(this.route.snapshot.paramMap.get('subjectId'));
  subjectName = signal('Materia');
  students = signal<Page<Student>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  tasksList = signal<Page<Task>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  studentPage = signal(1);
  taskPage = signal(1);
  cycles = signal<any[]>([]);
  cycleId = signal<number | null>(null);
  taskEditor = signal<Task | null | false>(false);
  taskName = '';
  taskDescription = '';
  taskDueAt = '';

  statusLabel(status: string): string {
    return { active: 'Activa', cancelled: 'Cancelada' }[status] ?? status;
  }

  constructor() {
    this.tasks.subjects(undefined, 1).subscribe((rows) => {
      this.subjectName.set(
        rows.items.find((subject) => Number(subject.id) === this.subjectId)?.name ?? 'Materia',
      );
    });
    this.cyclesApi.all().subscribe((rows) => {
      this.cycles.set(rows.filter((row) => row.status === 'active'));
      if (this.cycles().length === 1) this.cycleId.set(Number(this.cycles()[0].id));
      this.load();
    });
  }

  load(): void {
    if (this.cycles().length > 1 && !this.cycleId()) return;
    this.tasks
      .students(this.subjectId, this.cycleId() ?? undefined, this.studentPage())
      .subscribe((rows) => this.students.set(rows));
    this.loadTasks();
  }

  loadStudents(page: number): void {
    this.studentPage.set(page);
    this.tasks
      .students(this.subjectId, this.cycleId() ?? undefined, page)
      .subscribe((rows) => this.students.set(rows));
  }

  filterChanged(): void {
    this.studentPage.set(1);
    this.taskPage.set(1);
    this.load();
  }

  loadTasks(page = this.taskPage()): void {
    this.taskPage.set(page);
    this.tasks
      .tasks(this.subjectId, this.cycleId() ?? undefined, page)
      .subscribe((rows) => this.tasksList.set(rows));
  }

  createTask(): void {
    this.taskName = '';
    this.taskDescription = '';
    this.taskDueAt = '';
    this.taskEditor.set(null);
  }

  editTask(task: Task): void {
    this.taskName = task.name;
    this.taskDescription = task.description;
    this.taskDueAt = task.due_at;
    this.taskEditor.set(task);
  }

  saveTask(): void {
    const editing = this.taskEditor();
    if (editing === false || !this.taskName.trim() || !this.taskDueAt) return;
    const data = {
      name: this.taskName.trim(),
      description: this.taskDescription,
      due_at: this.taskDueAt,
    };
    const request = editing
      ? this.tasks.updateTask(editing.id, data)
      : this.tasks.createTask(this.subjectId, { ...data, cycle_id: this.cycleId()! });
    request.subscribe(() => {
      this.taskEditor.set(false);
      this.loadTasks();
    });
  }

  cancelTask(task: Task): void {
    if (confirm(`¿Cancelar la tarea «${task.name}»?`)) {
      this.tasks.cancelTask(task.id).subscribe(() => this.loadTasks());
    }
  }
}
