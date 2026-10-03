import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { StudyPlan, StudyPlansService } from '../study-plans.service';
import { ToastService } from '../../../core/services/toast.service';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';

@Component({
  selector: 'app-study-plans',
  standalone: true,
  imports: [ReactiveFormsModule, IconButtonComponent],
  templateUrl: './study-plans.component.html',
})
export class StudyPlansComponent {
  private readonly fb = inject(FormBuilder);
  private readonly api = inject(StudyPlansService);
  private readonly toast = inject(ToastService);
  rows = signal<StudyPlan[]>([]);
  formOpen = signal(false);
  editing: number | null = null;
  form = this.fb.nonNullable.group({
    code: ['', Validators.required],
    name: ['', Validators.required],
    status: this.fb.nonNullable.control<'active' | 'inactive'>('active'),
  });

  constructor() {
    this.reload();
  }
  reload(): void {
    this.api.list().subscribe((rows) => this.rows.set(rows));
  }
  open(row?: StudyPlan): void {
    this.editing = row ? Number(row.id) : null;
    this.form.reset(
      row
        ? { code: row.code, name: row.name, status: row.status }
        : { code: '', name: '', status: 'active' },
    );
    this.formOpen.set(true);
  }
  close(): void {
    this.formOpen.set(false);
    this.editing = null;
  }
  save(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.api.save(this.editing, this.form.getRawValue()).subscribe(() => {
      this.toast.show('Plan guardado.');
      this.close();
      this.reload();
    });
  }
  remove(row: StudyPlan): void {
    this.api.remove(Number(row.id)).subscribe(() => {
      this.toast.show('Plan eliminado.');
      this.reload();
    });
  }
  hasSubjects(row: StudyPlan): boolean {
    return Number(row.subjects_count ?? 0) > 0;
  }
}
