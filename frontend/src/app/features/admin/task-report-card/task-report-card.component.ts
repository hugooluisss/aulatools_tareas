import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ToastService } from '../../../core/services/toast.service';
import { ReportGroup, ReportStudent, ReportsService } from '../reports.service';

@Component({
  selector: 'app-task-report-card',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './task-report-card.component.html',
})
export class TaskReportCardComponent {
  private readonly reportsApi = inject(ReportsService);
  private readonly toast = inject(ToastService);
  groups = signal<ReportGroup[]>([]);
  students = signal<ReportStudent[]>([]);
  subjectId = signal('');
  selectedStudentIds = signal<number[]>([]);
  loading = signal(false);
  selectedGroup = computed(() =>
    this.groups().find((group) =>
      group.subjects.some((subject) => subject.id === Number(this.subjectId())),
    ),
  );

  constructor() {
    this.reportsApi.options().subscribe((options) => this.groups.set(options.groups));
  }

  loadStudents(subjectId: string): void {
    this.subjectId.set(subjectId);
    this.students.set([]);
    this.selectedStudentIds.set([]);
    if (!subjectId) return;
    this.reportsApi.students(Number(subjectId)).subscribe({
      next: (page) => this.students.set(page.items),
      error: (error: Error) => this.toast.show(error.message),
    });
  }

  isSelected(studentId: number): boolean {
    return this.selectedStudentIds().includes(studentId);
  }

  toggleStudent(studentId: number, checked: boolean): void {
    this.selectedStudentIds.update((ids) =>
      checked ? [...ids, studentId] : ids.filter((id) => id !== studentId),
    );
  }

  selectAll(checked: boolean): void {
    this.selectedStudentIds.set(checked ? this.students().map((student) => student.id) : []);
  }

  download(): void {
    if (!this.subjectId() || !this.selectedStudentIds().length || this.loading()) return;
    this.loading.set(true);
    this.reportsApi.taskReportCard(Number(this.subjectId()), this.selectedStudentIds()).subscribe({
      next: (blob) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'boleta-tareas.pdf';
        link.click();
        URL.revokeObjectURL(url);
        this.loading.set(false);
      },
      error: (error: Error) => {
        this.toast.show(error.message);
        this.loading.set(false);
      },
    });
  }
}
