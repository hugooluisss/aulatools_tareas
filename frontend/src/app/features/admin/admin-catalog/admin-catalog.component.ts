import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';
import { ToastService } from '../../../core/services/toast.service';
import { StudentsService } from '../students.service';
import { TeachersService } from '../teachers.service';
import { CyclesService } from '../cycles.service';
import { SubjectsService } from '../subjects.service';
import { GroupsService } from '../groups.service';
import { StudyPlansService, StudyPlan } from '../study-plans.service';
import { InscriptionsService, Inscription } from '../inscriptions.service';
import { forkJoin } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { ImageCroppedEvent, ImageCropperComponent } from 'ngx-image-cropper';
import { environment } from '../../../../environments/environment';
import { StudentNotesModalComponent } from '../../tasks/student-notes-modal/student-notes-modal.component';
import { Page } from '../../../core/models/page';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ColumnComponent } from '../../../shared/data-table/column.component';
import { StudentRow } from '../students.service';
import { TeacherRow } from '../teachers.service';
import { CycleRow } from '../cycles.service';
import { GroupRow } from '../groups.service';
import { SubjectRow } from '../subjects.service';

@Component({
  selector: 'app-admin-catalog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    IconButtonComponent,
    ImageCropperComponent,
    StudentNotesModalComponent,
    DataTableComponent,
    ColumnComponent,
  ],
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
  private readonly plansApi = inject(StudyPlansService);
  private readonly inscriptionsApi = inject(InscriptionsService);
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
  readonly activeSubjectsOnly = this.kind === 'groups';
  readonly statusLabels: Record<string, string> = {
    active: 'Activo',
    inactive: 'Inactivo',
    finished: 'Finalizado',
    in_progress: 'En curso',
  };
  page = signal<Page<StudentRow | TeacherRow | CycleRow | GroupRow | SubjectRow>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  currentPage = signal(1);
  cycles = signal<CycleRow[]>([]);
  activeCycles = computed(() => this.cycles().filter((cycle) => cycle.status === 'active'));
  groups = signal<GroupRow[]>([]);
  teachers = signal<TeacherRow[]>([]);
  subjects = signal<SubjectRow[]>([]);
  studyPlans = signal<StudyPlan[]>([]);
  cycleSubjects = computed(() => this.subjects().filter((subject) => subject.status === 'active'));
  subjectEnrollment = signal<any | null>(null);
  subjectCandidates = signal<Inscription[]>([]);
  selectedSubjectStudentIds = signal<number[]>([]);
  loadingSubjectCandidates = signal(false);
  selectedStudents = signal<any[] | null>(null);
  selectedStudentSubjectId = signal<number | null>(null);
  selectedRecord = signal<any | null>(null);
  notesStudentId = signal<number | null>(null);
  error = signal('');
  editing: number | null = null;
  showForm = signal(false);
  cropImage = signal<string | null>(null);
  cropResult = signal<Blob | null>(null);
  croppedPhoto = signal<Blob | null>(null);
  photoUrl = computed(() => {
    const path = this.form.controls.photo_url.value;
    return path ? `${environment.apiUrl}${path}` : null;
  });
  form = this.fb.nonNullable.group({
    first_name: [''],
    last_name: [''],
    enrollment_number: [''],
    email: [''],
    password: [''],
    birth_date: [''],
    status: ['active'],
    name: [''],
    starts_on: [''],
    ends_on: [''],
    cycle_id: [0],
    teacher_id: [0],
    subject_ids: [[] as number[]],
    code: [''],
    plan_id: [null as number | null],
    new_password: [''],
    address: [''],
    phone: [''],
    contact_phone: [''],
    guardian_name: [''],
    guardian_phone: [''],
    photo_url: [''],
  });
  constructor() {
    this.configureForm();
    this.reload();
    if (this.kind === 'subjects' || this.kind === 'groups') {
      this.cyclesApi.all().subscribe((r) => this.cycles.set(r));
      this.teachersApi.all().subscribe((r) => this.teachers.set(r));
      this.subjectsApi
        .all(this.kind === 'groups' ? 'active' : undefined)
        .subscribe((r) => this.subjects.set(r));
    }
    if (this.kind === 'subjects')
      this.plansApi
        .list()
        .subscribe((r) => this.studyPlans.set(r.filter((p) => p.status === 'active')));
  }
  reload(): void {
    const page = this.currentPage();
    const onPage = <T extends StudentRow | TeacherRow | CycleRow | GroupRow | SubjectRow>(
      result: Page<T>,
    ) => this.setPage(result);
    if (this.kind === 'students') this.studentsApi.list(page).subscribe(onPage);
    else if (this.kind === 'teachers') this.teachersApi.list(page).subscribe(onPage);
    else if (this.kind === 'cycles') this.cyclesApi.list(page).subscribe(onPage);
    else if (this.kind === 'subjects') this.subjectsApi.list(undefined, page).subscribe(onPage);
    else this.groupsApi.list(page).subscribe(onPage);
  }
  pageChange(page: number): void {
    this.currentPage.set(page);
    this.reload();
  }
  private setPage<T extends StudentRow | TeacherRow | CycleRow | GroupRow | SubjectRow>(
    result: Page<T>,
  ): void {
    if (!result.items.length && result.page > 1 && result.total > 0) {
      this.currentPage.set(result.page - 1);
      this.reload();
    } else this.page.set(result as Page<StudentRow | TeacherRow | CycleRow | GroupRow | SubjectRow>);
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
      const { first_name, last_name, enrollment_number, email, password, birth_date, status } =
        this.form.getRawValue();
      const data =
        this.kind === 'students'
          ? {
              first_name,
              last_name,
              enrollment_number,
              email,
              ...(password ? { password } : {}),
              birth_date,
              status,
              address: this.form.controls.address.value,
              contact_phone: this.form.controls.contact_phone.value,
              guardian_name: this.form.controls.guardian_name.value,
              guardian_phone: this.form.controls.guardian_phone.value,
            }
          : {
              first_name,
              last_name,
              email,
              ...(password ? { password } : {}),
              address: this.form.controls.address.value,
              phone: this.form.controls.phone.value,
            };
      api.save(this.editing, data).subscribe((saved: any) => {
        const photo = this.croppedPhoto();
        if (this.kind === 'teachers' && photo)
          this.teachersApi
            .photo(this.editing ?? Number(saved.id), photo)
            .subscribe(() => this.done('Usuario guardado.'));
        else this.done('Usuario guardado.');
      });
    } else if (this.kind === 'cycles') {
      const { name, starts_on, ends_on } = this.form.getRawValue();
      this.cyclesApi
        .save(this.editing, { name, starts_on, ends_on })
        .subscribe(() => this.done('Ciclo guardado.'));
    } else if (this.kind === 'subjects') {
      const { name, code, plan_id, teacher_id } = this.form.getRawValue();
      this.subjectsApi
        .save(this.editing, {
          name,
          code,
          plan_id: plan_id || null,
          teacher_id,
          ...(this.editing ? { status: this.form.getRawValue().status } : {}),
        })
        .subscribe(() => this.done('Materia guardada.'));
    } else {
      const { name, cycle_id, subject_ids, teacher_id } = this.form.getRawValue();
      this.groupsApi
        .save(this.editing, {
          name,
          cycle_id,
          subject_ids: subject_ids.map(Number),
          teacher_id: teacher_id ? Number(teacher_id) : null,
        })
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
    if ('remove' in api) api.remove(id).subscribe(() => {
      if (this.page().items.length === 1 && this.currentPage() > 1) this.currentPage.update((page) => page - 1);
      this.done('Registro eliminado.');
    });
  }
  confirmRemove(): void {
    if (this.editing && window.confirm('¿Eliminar este estudiante? No se puede deshacer.'))
      this.remove(this.editing);
  }
  finish(id: number): void {
    this.cyclesApi.finish(id).subscribe(() => this.done('Ciclo finalizado.'));
  }
  viewStudents(id: number): void {
    this.selectedStudentSubjectId.set(id);
    this.subjectCycleId.set(
      this.activeCycles().length === 1 ? Number(this.activeCycles()[0].id) : null,
    );
    if (this.activeCycles().length > 1) this.selectedStudents.set([]);
    else this.loadSubjectStudents();
  }
  loadSubjectStudents(): void {
    if (this.selectedStudentSubjectId() === null || !this.subjectCycleId()) return;
    this.subjectsApi
      .students(this.selectedStudentSubjectId()!, this.subjectCycleId() ?? undefined)
      .subscribe((rows) => this.selectedStudents.set(rows));
  }
  closeStudents(): void {
    this.selectedStudents.set(null);
    this.selectedStudentSubjectId.set(null);
  }
  canAddSubjectStudents(subject: any): boolean {
    return this.activeCycles().length > 0;
  }
  subjectCycleId = signal<number | null>(null);
  hasEnrolledStudents(subject: any): boolean {
    return Number(subject.students_count ?? 0) > 0;
  }
  hasCandidateGroups(): boolean {
    return this.subjectCandidates().some((student) => student.group_name);
  }
  openAddStudents(subject: any): void {
    this.subjectEnrollment.set(subject);
    this.subjectCycleId.set(
      this.activeCycles().length === 1 ? Number(this.activeCycles()[0].id) : null,
    );
    this.selectedSubjectStudentIds.set([]);
    this.loadSubjectCandidates();
  }
  loadSubjectCandidates(): void {
    const subject = this.subjectEnrollment();
    const cycleId = this.subjectCycleId();
    if (!subject || !cycleId) {
      this.subjectCandidates.set([]);
      return;
    }
    this.loadingSubjectCandidates.set(true);
    forkJoin({
      inscriptions: this.inscriptionsApi.list({ cycle_id: cycleId }),
      enrolled: this.subjectsApi.students(Number(subject.id), cycleId),
    })
      .pipe(finalize(() => this.loadingSubjectCandidates.set(false)))
      .subscribe(({ inscriptions, enrolled }) => {
        const enrolledIds = new Set(enrolled.map((student) => Number(student.id)));
        this.subjectCandidates.set(
          inscriptions
            .filter((row) => !enrolledIds.has(Number(row.student_id)))
            .sort((a, b) =>
              `${a.student_last_name} ${a.student_first_name}`.localeCompare(
                `${b.student_last_name} ${b.student_first_name}`,
              ),
            ),
        );
      });
  }
  closeAddStudents(): void {
    this.subjectEnrollment.set(null);
    this.subjectCycleId.set(null);
    this.subjectCandidates.set([]);
    this.selectedSubjectStudentIds.set([]);
  }
  toggleSubjectStudents(checked: boolean): void {
    this.selectedSubjectStudentIds.set(
      checked ? this.subjectCandidates().map((row) => Number(row.student_id)) : [],
    );
  }
  toggleSubjectStudent(id: number | string, checked: boolean): void {
    const value = Number(id);
    this.selectedSubjectStudentIds.update((ids) =>
      checked ? [...new Set([...ids, value])] : ids.filter((item) => item !== value),
    );
  }
  isSubjectStudentSelected(id: number | string): boolean {
    return this.selectedSubjectStudentIds().includes(Number(id));
  }
  addSelectedStudents(): void {
    const subject = this.subjectEnrollment();
    const studentIds = this.selectedSubjectStudentIds();
    if (!subject || !studentIds.length) return;
    this.subjectsApi
      .enrollBulk(Number(subject.id), this.subjectCycleId()!, studentIds)
      .subscribe(() => {
        this.toast.show('Estudiantes agregados a la materia.');
        this.closeAddStudents();
        this.reload();
        this.viewStudents(Number(subject.id));
      });
  }
  view(row: any): void {
    this.selectedRecord.set(row);
  }
  detail(row: any): string {
    if (this.kind === 'students')
      return row.enrollment
        ? `${row.enrollment.group_name} · ${row.enrollment.cycle_name}`
        : 'Sin inscripción';
    if (this.kind === 'groups') return row.cycle_name || '—';
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
  private done(message: string): void {
    this.toast.show(message);
    this.closeForm();
    this.reload();
  }
  private resetForm(): void {
    this.croppedPhoto.set(null);
    this.cropResult.set(null);
    this.cropImage.set(null);
    this.form.reset({
      status: 'active',
      cycle_id: 0,
      teacher_id: 0,
      subject_ids: [],
    });
  }
  selectPhoto(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    this.cropImage.set('');
    const reader = new FileReader();
    reader.onload = () => this.cropImage.set(String(reader.result));
    reader.readAsDataURL(file);
  }
  crop(event: ImageCroppedEvent): void {
    if (event.blob) this.cropResult.set(event.blob);
  }
  confirmCrop(): void {
    if (this.cropResult()) this.croppedPhoto.set(this.cropResult());
    this.cropImage.set(null);
  }
  removePhoto(): void {
    this.croppedPhoto.set(null);
    if (!this.editing) return;
    this.teachersApi.removePhoto(this.editing).subscribe((teacher) => {
      this.form.patchValue({ photo_url: teacher.photo_url });
      this.toast.show('Fotografía eliminada.');
    });
  }
  cancelCrop(): void {
    this.cropResult.set(null);
    this.cropImage.set(null);
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
      if (this.kind === 'students') required('birth_date');
      if (this.kind === 'students') required('enrollment_number');
      if (!this.editing) this.form.controls.password.addValidators(Validators.required);
    }
    if (this.kind === 'cycles') ['name', 'starts_on', 'ends_on'].forEach(required);
    if (this.kind === 'subjects') {
      ['name', 'code', 'teacher_id'].forEach(required);
      this.form.controls.teacher_id.addValidators(Validators.min(1));
    }
    if (this.kind === 'groups') {
      required('name');
      required('cycle_id');
      this.form.controls.cycle_id.addValidators(Validators.min(1));
    }
  }
}
