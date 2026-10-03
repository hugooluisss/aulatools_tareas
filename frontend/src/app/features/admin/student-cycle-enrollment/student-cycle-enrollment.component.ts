import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CyclesService } from '../cycles.service';
import { GroupsService } from '../groups.service';
import { EnrollmentCandidate, InscriptionsService } from '../inscriptions.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-student-cycle-enrollment',
  standalone: true,
  templateUrl: './student-cycle-enrollment.component.html',
})
export class StudentCycleEnrollmentComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly cyclesApi = inject(CyclesService);
  private readonly groupsApi = inject(GroupsService);
  private readonly inscriptionsApi = inject(InscriptionsService);
  private readonly toast = inject(ToastService);
  readonly type = this.route.snapshot.data['type'] as 'enrollment' | 'reenrollment';
  readonly isReenrollment = this.type === 'reenrollment';
  readonly title = this.isReenrollment ? 'Reinscripciones' : 'Inscripciones';
  readonly action = this.isReenrollment ? 'Reinscribir seleccionados' : 'Inscribir seleccionados';
  candidates = signal<EnrollmentCandidate[]>([]);
  cycles = signal<any[]>([]);
  groups = signal<any[]>([]);
  selectedIds = signal<number[]>([]);
  cycleId = signal(0);
  groupId = signal(0);
  readonly activeCycles = computed(() => {
    const previousCycleIds = new Set(
      this.candidates()
        .filter((student) => this.selectedIds().includes(Number(student.student_id)))
        .map((student) => Number(student.cycle_id)),
    );
    return this.cycles().filter(
      (cycle) =>
        cycle.status === 'active' &&
        (!this.isReenrollment || !previousCycleIds.has(Number(cycle.id))),
    );
  });
  readonly availableGroups = computed(() =>
    this.groups().filter((group) => Number(group.cycle_id) === Number(this.cycleId())),
  );

  constructor() {
    this.cyclesApi.list().subscribe((rows) => this.cycles.set(rows));
    this.groupsApi.list().subscribe((rows) => this.groups.set(rows));
    this.reload();
  }

  reload(): void {
    const request = this.isReenrollment
      ? this.inscriptionsApi.reenrollable()
      : this.inscriptionsApi.aspirants();
    request.subscribe((rows) => {
      this.candidates.set(rows);
      this.selectedIds.set([]);
    });
  }

  toggleAll(checked: boolean): void {
    this.selectedIds.set(checked ? this.candidates().map((row) => Number(row.student_id)) : []);
  }

  toggle(id: number | string, checked: boolean): void {
    const value = Number(id);
    this.selectedIds.update((ids) =>
      checked ? [...new Set([...ids, value])] : ids.filter((item) => item !== value),
    );
  }

  isSelected(id: number | string): boolean {
    return this.selectedIds().includes(Number(id));
  }

  submit(): void {
    if (!this.selectedIds().length || !this.cycleId() || !this.groupId()) return;
    this.inscriptionsApi
      .bulk({
        type: this.type,
        student_ids: this.selectedIds(),
        cycle_id: this.cycleId(),
        group_id: this.groupId(),
      })
      .subscribe(() => {
        this.toast.show(
          this.isReenrollment ? 'Reinscripción completada.' : 'Inscripción completada.',
        );
        this.cycleId.set(0);
        this.groupId.set(0);
        this.reload();
      });
  }
}
