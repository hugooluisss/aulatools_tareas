import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';
import { ToastService } from '../../../core/services/toast.service';
import { StudentsService } from '../students.service';
import { TeachersService } from '../teachers.service';
import { CyclesService } from '../cycles.service';
import { SubjectsService } from '../subjects.service';
import { GroupsService } from '../groups.service';

@Component({
  selector: 'app-admin-catalog',
  standalone: true,
  imports: [ReactiveFormsModule, IconButtonComponent],
  templateUrl: './admin-catalog.component.html',
  styleUrl: './admin-catalog.component.scss',
})
export class AdminCatalogComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  private readonly studentsApi = inject(StudentsService);
  private readonly teachersApi = inject(TeachersService);
  private readonly cyclesApi = inject(CyclesService);
  private readonly subjectsApi = inject(SubjectsService);
  private readonly groupsApi = inject(GroupsService);
  private readonly toast = inject(ToastService);
  readonly kind = this.route.snapshot.data['kind'] as string;
  readonly title = (
    {
      students: 'Estudiantes',
      teachers: 'Profesores',
      cycles: 'Ciclos',
      subjects: 'Materias',
      groups: 'Grupos',
    } as Record<string, string>
  )[this.kind];
  readonly statusLabels: Record<string, string> = {
    active: 'Activo',
    inactive: 'Inactivo',
    finished: 'Finalizado',
    in_progress: 'En curso',
  };
  rows = signal<any[]>([]);
  cycles = signal<any[]>([]);
  teachers = signal<any[]>([]);
  subjects = signal<any[]>([]);
  students = signal<any[]>([]);
  selectedStudents = signal<any[] | null>(null);
  selectedRecord = signal<any | null>(null);
  error = signal('');
  editing: number | null = null;
  showForm = signal(false);
  form = this.fb.nonNullable.group({
    first_name: [''],
    last_name: [''],
    email: [''],
    password: [''],
    enrollment_number: [''],
    birth_date: [''],
    status: ['active'],
    name: [''],
    starts_on: [''],
    ends_on: [''],
    cycle_id: [0],
    teacher_id: [0],
    subject_ids: [[] as number[]],
    student_id: [0],
    new_password: [''],
  });
  constructor() {
    this.configureForm();
    this.reload();
    if (this.kind === 'subjects' || this.kind === 'groups') {
      this.cyclesApi.list().subscribe((r) => this.cycles.set(r));
      this.teachersApi.list().subscribe((r) => this.teachers.set(r));
      this.subjectsApi.list().subscribe((r) => this.subjects.set(r));
      this.studentsApi.list().subscribe((r) => this.students.set(r));
    }
  }
  reload(): void {
    const api =
      this.kind === 'students'
        ? this.studentsApi
        : this.kind === 'teachers'
          ? this.teachersApi
          : this.kind === 'cycles'
            ? this.cyclesApi
            : this.kind === 'subjects'
              ? this.subjectsApi
              : this.groupsApi;
    api.list().subscribe((r) => this.rows.set(r));
  }
  openNew(): void {
    this.editing = null;
    this.error.set('');
    this.resetForm();
    this.showForm.set(true);
  }
  closeForm(): void {
    this.showForm.set(false);
    this.editing = null;
    this.error.set('');
    this.resetForm();
  }
  edit(row: any): void {
    this.editing = row.id;
    this.showForm.set(true);
    this.form.controls.password.removeValidators(Validators.required);
    this.form.controls.password.updateValueAndValidity();
    this.form.patchValue({ ...row, subject_ids: row.subjects?.map((s: any) => s.id) ?? [] });
  }
  save(): void {
    this.error.set('');
    if (this.kind === 'students' || this.kind === 'teachers') {
      if (this.editing) this.form.controls.password.removeValidators(Validators.required);
      else this.form.controls.password.addValidators(Validators.required);
      this.form.controls.password.updateValueAndValidity();
    }
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const { starts_on, ends_on } = this.form.getRawValue();
    if (this.kind === 'cycles' && ends_on < starts_on) {
      this.error.set('La fecha de fin debe ser posterior a la fecha de inicio.');
      return;
    }
    if (this.kind === 'students' || this.kind === 'teachers') {
      const api = this.kind === 'students' ? this.studentsApi : this.teachersApi;
      const { first_name, last_name, email, password, enrollment_number, birth_date, status } =
        this.form.getRawValue();
      const data =
        this.kind === 'students'
          ? {
              first_name,
              last_name,
              email,
              ...(password ? { password } : {}),
              enrollment_number,
              birth_date,
              status,
            }
          : { first_name, last_name, email, ...(password ? { password } : {}) };
      api.save(this.editing, data).subscribe(() => this.done('Usuario guardado.'));
    } else if (this.kind === 'cycles') {
      const { name, starts_on, ends_on } = this.form.getRawValue();
      this.cyclesApi
        .save(this.editing, { name, starts_on, ends_on })
        .subscribe(() => this.done('Ciclo guardado.'));
    } else if (this.kind === 'subjects') {
      const { name, cycle_id, teacher_id } = this.form.getRawValue();
      this.subjectsApi
        .save(this.editing, {
          name,
          cycle_id,
          teacher_id,
          ...(this.editing ? { status: this.form.getRawValue().status } : {}),
        })
        .subscribe(() => this.done('Materia guardada.'));
    } else {
      const { name, subject_ids } = this.form.getRawValue();
      this.groupsApi
        .save(this.editing, { name, subject_ids })
        .subscribe(() => this.done('Grupo guardado.'));
    }
  }
  remove(id: number): void {
    const api =
      this.kind === 'students'
        ? this.studentsApi
        : this.kind === 'teachers'
          ? this.teachersApi
          : this.kind === 'subjects'
            ? this.subjectsApi
            : this.groupsApi;
    if ('remove' in api) api.remove(id).subscribe(() => this.done('Registro eliminado.'));
  }
  confirmRemove(): void {
    if (this.editing && window.confirm('¿Eliminar este estudiante? No se puede deshacer.'))
      this.remove(this.editing);
  }
  finish(id: number): void {
    this.cyclesApi.finish(id).subscribe(() => this.done('Ciclo finalizado.'));
  }
  viewStudents(id: number): void {
    this.subjectsApi.students(id).subscribe((r) => this.selectedStudents.set(r));
  }
  view(row: any): void {
    this.selectedRecord.set(row);
  }
  detail(row: any): string {
    return (
      row.email ||
      row.enrollment_number ||
      row.starts_on ||
      row.subjects?.map((s: any) => s.name).join(', ') ||
      '—'
    );
  }
  toggle(row: any): void {
    this.studentsApi
      .status(row.id, row.status === 'active' ? 'inactive' : 'active')
      .subscribe(() => this.done('Estado actualizado.'));
  }
  reset(id: number): void {
    const password = window.prompt('Nueva contraseña (mínimo 8 caracteres)');
    if (password && password.length >= 8)
      (this.kind === 'students' ? this.studentsApi : this.teachersApi)
        .reset(id, password)
        .subscribe(() => this.toast.show('Contraseña restablecida.'));
  }
  enroll(id: number, studentId: number): void {
    const action =
      this.kind === 'groups'
        ? this.groupsApi.enroll(id, studentId)
        : this.subjectsApi.enroll(id, studentId);
    action.subscribe(() => this.toast.show('Estudiante inscrito.'));
  }
  private done(message: string): void {
    this.toast.show(message);
    this.closeForm();
    this.reload();
  }
  private resetForm(): void {
    this.form.reset({
      status: 'active',
      cycle_id: 0,
      teacher_id: 0,
      student_id: 0,
      subject_ids: [],
    });
  }
  private configureForm(): void {
    const required = (name: string) =>
      this.form.controls[name as keyof typeof this.form.controls].addValidators(
        Validators.required,
      );
    if (this.kind === 'students' || this.kind === 'teachers') {
      ['first_name', 'last_name', 'email'].forEach(required);
      this.form.controls.email.addValidators(Validators.email);
      this.form.controls.password.addValidators(Validators.minLength(8));
      if (this.kind === 'students') ['enrollment_number', 'birth_date'].forEach(required);
      if (!this.editing) this.form.controls.password.addValidators(Validators.required);
    }
    if (this.kind === 'cycles') ['name', 'starts_on', 'ends_on'].forEach(required);
    if (this.kind === 'subjects') {
      ['name', 'cycle_id', 'teacher_id'].forEach(required);
      this.form.controls.cycle_id.addValidators(Validators.min(1));
      this.form.controls.teacher_id.addValidators(Validators.min(1));
    }
    if (this.kind === 'groups') required('name');
  }
}
