import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ReportGroup, ReportsService } from '../reports.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-attendance-report',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './attendance-report.component.html',
})
export class AttendanceReportComponent {
  private readonly reportsApi = inject(ReportsService);
  private readonly toast = inject(ToastService);
  groups = signal<ReportGroup[]>([]);
  groupId = signal('');
  subjectId = signal('');
  startDate = signal(new Date().toLocaleDateString('en-CA'));
  days = signal(5);
  loading = signal(false);
  selectedGroup = computed(() =>
    this.groups().find((group) => group.id === Number(this.groupId())),
  );

  constructor() {
    this.reportsApi.options().subscribe((options) => this.groups.set(options.groups));
  }

  download(): void {
    if (!this.groupId() || !this.startDate() || this.days() < 1 || this.days() > 14) return;
    this.loading.set(true);
    this.reportsApi
      .attendance({
        group_id: Number(this.groupId()),
        ...(this.subjectId() ? { subject_id: Number(this.subjectId()) } : {}),
        start_date: this.startDate(),
        days: Number(this.days()),
      })
      .subscribe({
        next: (blob) => {
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = 'lista-de-asistencia.pdf';
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
