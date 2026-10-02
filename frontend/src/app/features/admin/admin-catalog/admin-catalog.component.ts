import { Component, inject } from '@angular/core';
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
  rows: any[] = [];
  cycles: any[] = [];
  teachers: any[] = [];
  subjects: any[] = [];
  students: any[] = [];
  selectedStudents: any[] | null = null;
  selectedRecord: any | null = null;
  error = '';
  editing: number | null = null;
  showForm = false;
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
      this.cyclesApi.list().subscribe((r) => (this.cycles = r.data));
      this.teachersApi.list().subscribe((r) => (this.teachers = r.data));
      this.subjectsApi.list().subscribe((r) => (this.subjects = r.data));
      this.studentsApi.list().subscribe((r) => (this.students = r.data));
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
    api.list().subscribe((r) => (this.rows = r.data));
  }
  openNew(): void {
    this.editing = null;
    this.error = '';
    this.resetForm();
    this.showForm = true;
  }
  closeForm(): void {
    this.showForm = false;
    this.editing = null;
    this.error = '';
    this.resetForm();
  }
  edit(row: any): void {
    this.editing = row.id;
    this.showForm = true;
    this.form.controls.password.removeValidators(Validators.required);
    this.form.controls.password.updateValueAndValidity();
    this.form.patchValue({ ...row, subject_ids: row.subjects?.map((s: any) => s.id) ?? [] });
  }
  save(): void {
    this.error = '';
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
      this.error = 'La fecha de fin debe ser posterior a la fecha de inicio.';
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
  finish(id: number): void {
    this.cyclesApi.finish(id).subscribe(() => this.done('Ciclo finalizado.'));
  }
  viewStudents(id: number): void {
    this.subjectsApi.students(id).subscribe((r) => (this.selectedStudents = r.data));
  }
  view(row: any): void {
    this.selectedRecord = row;
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
