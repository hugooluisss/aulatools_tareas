import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { AdminCatalogComponent } from './admin-catalog.component';
import { StudentsService } from '../students.service';
import { StudentNotesService } from '../../tasks/student-notes.service';
import { TeachersService } from '../teachers.service';
import { CyclesService } from '../cycles.service';
import { SubjectsService } from '../subjects.service';
import { GroupsService } from '../groups.service';
import { InscriptionsService } from '../inscriptions.service';
import { ToastService } from '../../../core/services/toast.service';
import { StudyPlansService } from '../study-plans.service';
import { environment } from '../../../../environments/environment';

describe('AdminCatalogComponent', () => {
  let fixture: ComponentFixture<AdminCatalogComponent>;
  let kind = 'cycles';
  const api = {
    list: () => of([]),
    save: () => of({}),
    remove: () => of(void 0),
    status: () => of({}),
    reset: () => of({}),
    finish: () => of({}),
    enroll: () => of({}),
    students: vi.fn(() => of([{ id: '99' }])),
    unenroll: () => of(void 0),
    enrollBulk: vi.fn(() => of([])),
    listPlans: () => of([]),
  };
  const inscriptionsApi = {
    list: vi.fn(() =>
      of([
        {
          id: 1,
          student_id: '9',
          student_first_name: 'Ana',
          student_last_name: 'Zeta',
          cycle_id: '14',
          cycle_name: '2026',
          group_id: '3',
          group_name: '1A',
        },
        {
          id: 2,
          student_id: '10',
          student_first_name: 'Luis',
          student_last_name: 'Alfa',
          cycle_id: '14',
          cycle_name: '2026',
          group_id: '3',
          group_name: '1A',
        },
      ]),
    ),
  };
  beforeEach(() => {
    kind = 'cycles';
    api.students.mockReturnValue(of([{ id: '99' }]));
    api.enrollBulk.mockClear();
    inscriptionsApi.list.mockClear();
    TestBed.configureTestingModule({
      imports: [AdminCatalogComponent],
      providers: [
        {
          provide: StudentNotesService,
          useValue: { list: () => of({ data: [], meta: {} }), add: () => of({}) },
        },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              data: {
                get kind() {
                  return kind;
                },
              },
            },
          },
        },
        { provide: StudentsService, useValue: api },
        { provide: TeachersService, useValue: api },
        { provide: CyclesService, useValue: api },
        { provide: SubjectsService, useValue: api },
        { provide: GroupsService, useValue: api },
        { provide: StudyPlansService, useValue: { list: () => of([]) } },
        { provide: InscriptionsService, useValue: inscriptionsApi },
        { provide: ToastService, useValue: { show: () => undefined } },
      ],
    });
    fixture = TestBed.createComponent(AdminCatalogComponent);
    fixture.detectChanges();
  });
  it('submits cycle fields and refreshes the list', () => {
    const component = fixture.componentInstance;
    component.form.patchValue({ name: '2026', starts_on: '2026-01-01', ends_on: '2026-12-31' });
    component.save();
    expect(component.title).toBe('Ciclos');
    expect(component.rows()).toEqual([]);
  });

  it('shows the student enrollment in the read-only table', () => {
    const component = fixture.componentInstance;
    Object.defineProperty(component, 'kind', { value: 'students' });
    expect(component.detail({ enrollment: null })).toBe('Sin inscripción');
    expect(component.detail({ enrollment: { group_name: '1A', cycle_name: '2026' } })).toBe(
      '1A · 2026',
    );
  });

  it('offers active catalog subjects regardless of the group cycle', () => {
    const component = fixture.componentInstance;
    component.form.controls.cycle_id.setValue(14);
    component.subjects.set([
      { id: '7', status: 'active' },
      { id: '8', status: 'inactive' },
    ]);
    expect(component.cycleSubjects().map((subject) => subject.id)).toEqual(['7']);
  });

  it('offers an optional group teacher and submits its id', () => {
    fixture.destroy();
    kind = 'groups';
    const saveGroup = vi.fn(() => of({}));
    api.save = saveGroup;
    TestBed.resetTestingModule();
    TestBed.configureTestingModule({
      imports: [AdminCatalogComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { data: { kind } } } },
        { provide: StudentsService, useValue: api },
        { provide: TeachersService, useValue: api },
        { provide: CyclesService, useValue: api },
        { provide: SubjectsService, useValue: api },
        { provide: GroupsService, useValue: api },
        { provide: StudyPlansService, useValue: { list: () => of([]) } },
        { provide: InscriptionsService, useValue: inscriptionsApi },
        { provide: ToastService, useValue: { show: () => undefined } },
      ],
    });
    fixture = TestBed.createComponent(AdminCatalogComponent);
    const component = fixture.componentInstance;
    component.teachers.set([{ id: '8', first_name: 'Luz', last_name: 'Sol' }]);
    component.openNew();
    fixture.detectChanges();

    const teacherSelect = fixture.nativeElement.querySelector('#group-teacher');
    expect(teacherSelect.textContent).toContain('Sin profesor');
    expect(teacherSelect.textContent).toContain('Luz Sol');

    component.form.patchValue({ name: '1A', cycle_id: 14, teacher_id: 8 });
    component.form.controls.subject_ids.setValue([3]);
    component.save();
    expect(saveGroup).toHaveBeenCalledWith(null, {
      name: '1A',
      cycle_id: 14,
      subject_ids: [3],
      teacher_id: 8,
    });
  });

  it('does not render enrollment controls in the student modal', () => {
    const component = fixture.componentInstance;
    Object.defineProperty(component, 'kind', { value: 'students' });
    component.openNew();
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).not.toContain('Inscripción');
  });

  it('saves student contact fields and renders teacher contact and WhatsApp controls', () => {
    const component = fixture.componentInstance;
    Object.defineProperty(component, 'kind', { value: 'students' });
    component.form.patchValue({
      first_name: 'Ana',
      last_name: 'Luz',
      email: 'ana@example.com',
      enrollment_number: 'A1',
      birth_date: '2010-01-01',
      address: 'Centro',
      contact_phone: '525512345678',
      guardian_name: 'María',
      guardian_phone: '525598765432',
    });
    component.form.controls.password.setValue('password1');
    component.save();
    expect(api.save).toHaveBeenCalledWith(
      null,
      expect.objectContaining({
        address: 'Centro',
        contact_phone: '525512345678',
        guardian_name: 'María',
        guardian_phone: '525598765432',
      }),
    );

    Object.defineProperty(component, 'kind', { value: 'teachers' });
    component.openNew();
    component.form.controls.photo_url.setValue('/uploads/teachers/3.jpg');
    fixture.detectChanges();
    expect(component.photoUrl()).toBe(`${environment.apiUrl}/uploads/teachers/3.jpg`);
    expect(fixture.nativeElement.querySelector('#teacher-address')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('#teacher-photo')).not.toBeNull();
    component.rows.set([{ id: 3, first_name: 'Luis', last_name: 'Sol', phone: '525512345678' }]);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('tbody a').getAttribute('href')).toBe(
      'https://wa.me/525512345678',
    );
  });

  it('adds only not-yet-enrolled cycle students through the bulk endpoint', () => {
    const component = fixture.componentInstance;
    Object.defineProperty(component, 'kind', { value: 'subjects' });
    component.cycles.set([{ id: '14', status: 'active' }]);
    component.rows.set([{ id: '31', name: 'Matemáticas', cycle_id: '14' }]);
    fixture.detectChanges();
    const addButton = fixture.nativeElement.querySelector('[aria-label="Agregar estudiante"]');
    expect(addButton.querySelector('i').classList).toContain('bi-person-plus');
    component.openAddStudents({ id: '31', name: 'Matemáticas', cycle_id: '14' });
    fixture.detectChanges();

    expect(component.canAddSubjectStudents({ cycle_id: 14 })).toBe(true);
    expect(component.subjectCandidates().map((row) => row.student_id)).toEqual(['10', '9']);
    const candidateTable = fixture.nativeElement
      .querySelector('#add-students-title')
      .closest('.modal')
      .querySelector('table');
    expect(candidateTable.className).toBe('table table-hover align-middle mb-0');
    expect(candidateTable.querySelector('thead').textContent).toContain('Matrícula');
    expect(candidateTable.querySelector('thead').textContent).toContain('Nombre');
    expect(candidateTable.querySelector('thead').textContent).toContain('Apellidos');
    expect(candidateTable.querySelector('thead').textContent).toContain('Grupo');
    expect(candidateTable.querySelectorAll('tbody tr')).toHaveLength(2);
    expect(
      candidateTable.querySelector('input[aria-label="Seleccionar todos los estudiantes"]'),
    ).not.toBeNull();
    expect(
      candidateTable.querySelector('input[aria-label="Seleccionar estudiante 9"]'),
    ).not.toBeNull();

    api.students.mockReturnValue(of([{ id: '9' }]));
    component.openAddStudents({ id: '31', name: 'Matemáticas', cycle_id: '14' });
    expect(component.subjectCandidates().map((row) => row.student_id)).toEqual(['10']);
    component.toggleSubjectStudent('10', true);
    component.addSelectedStudents();

    expect(api.enrollBulk).toHaveBeenCalledWith(31, 14, [10]);
    expect(component.subjectEnrollment()).toBeNull();
  });

  it('opens the add-students modal from the rendered subject action', () => {
    const component = fixture.componentInstance;
    Object.defineProperty(component, 'kind', { value: 'subjects' });
    component.cycles.set([{ id: '14', status: 'active' }]);
    component.rows.set([{ id: '31', name: 'Matemáticas', cycle_id: '14' }]);
    fixture.detectChanges();

    fixture.nativeElement.querySelector('[aria-label="Agregar estudiante"]').click();
    fixture.detectChanges();

    expect(component.subjectEnrollment()).toEqual(component.rows()[0]);
    expect(fixture.nativeElement.querySelector('#add-students-title')).not.toBeNull();
  });

  it('disables actions for finished or enrolled subjects and shows students in a modal', () => {
    const component = fixture.componentInstance;
    Object.defineProperty(component, 'kind', { value: 'subjects' });
    component.cycles.set([{ id: '14', status: 'finished' }]);
    expect(component.canAddSubjectStudents({ cycle_id: 14 })).toBe(false);
    component.rows.set([{ id: '31', name: 'Matemáticas', cycle_id: '14' }]);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('[aria-label="Agregar estudiante"]').disabled).toBe(
      true,
    );

    component.rows.set([
      { id: '31', name: 'Matemáticas', cycle_id: '14', students_count: '2' },
      { id: '32', name: 'Historia', cycle_id: '14' },
    ]);
    component.cycles.set([{ id: '14', status: 'active' }]);
    fixture.detectChanges();
    const deleteButtons = fixture.nativeElement.querySelectorAll('[aria-label="Eliminar"]');
    expect(deleteButtons[0].disabled).toBe(true);
    expect(deleteButtons[1].disabled).toBe(false);

    api.students.mockReturnValue(
      of([{ id: '57', first_name: 'Ana', last_name: 'Zeta', status: 'active' }]),
    );
    fixture.nativeElement.querySelector('[aria-label="Ver estudiantes"]').click();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('#students-title')).not.toBeNull();
    expect(fixture.nativeElement.textContent).toContain('57');
    expect(fixture.nativeElement.textContent).toContain('Ana');
    expect(fixture.nativeElement.textContent).toContain('Zeta');
    expect(fixture.nativeElement.textContent).toContain('Activo');
    expect(
      fixture.nativeElement
        .querySelector('#students-title')
        .closest('.modal')
        .querySelector('table'),
    ).not.toBeNull();
    fixture.nativeElement
      .querySelector('#students-title')
      .parentElement.querySelector('[aria-label="Cerrar"]')
      .click();
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('#students-title')).toBeNull();

    Object.defineProperty(component, 'kind', { value: 'groups' });
    component.rows.set([{ id: 1, name: '1A' }]);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('.form-select-sm')).toBeNull();
    expect(fixture.nativeElement.textContent).not.toContain('Inscribir estudiante');
  });
});
