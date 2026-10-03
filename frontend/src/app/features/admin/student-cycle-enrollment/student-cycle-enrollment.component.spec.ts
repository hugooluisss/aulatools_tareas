import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { CyclesService } from '../cycles.service';
import { GroupsService } from '../groups.service';
import { InscriptionsService } from '../inscriptions.service';
import { ToastService } from '../../../core/services/toast.service';
import { StudentCycleEnrollmentComponent } from './student-cycle-enrollment.component';

describe('StudentCycleEnrollmentComponent', () => {
  let fixture: ComponentFixture<StudentCycleEnrollmentComponent>;
  let api: any;

  beforeEach(() => {
    api = {
      list: () =>
        of([
          { id: '14', name: 'Ciclo 14', status: 'active' },
          { id: '15', name: 'Ciclo 15', status: 'active' },
        ]),
      aspirants: () => of([]),
      reenrollable: () =>
        of([
          {
            student_id: '57',
            first_name: 'A',
            last_name: 'B',
            cycle_id: '14',
            cycle_name: 'Ciclo 14',
            group_id: '11',
            group_name: '1A',
          },
        ]),
      bulk: vi.fn(() => of([])),
    };
    TestBed.configureTestingModule({
      imports: [StudentCycleEnrollmentComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { data: { type: 'reenrollment' } } } },
        { provide: CyclesService, useValue: api },
        { provide: GroupsService, useValue: { list: () => of([{ id: '11', cycle_id: '15' }]) } },
        { provide: InscriptionsService, useValue: api },
        { provide: ToastService, useValue: { show: () => undefined } },
      ],
    });
    fixture = TestBed.createComponent(StudentCycleEnrollmentComponent);
    fixture.detectChanges();
  });

  it('excludes previous cycles for selected students and filters groups numerically', () => {
    const component = fixture.componentInstance;
    component.toggle('57', true);
    component.cycleId.set(15);
    component.groupId.set(11);
    expect(component.activeCycles().map((cycle) => Number(cycle.id))).toEqual([15]);
    expect(component.availableGroups().map((group: any) => Number(group.id))).toEqual([11]);
    component.submit();
    expect(api.bulk).toHaveBeenCalledWith({
      type: 'reenrollment',
      student_ids: [57],
      cycle_id: 15,
      group_id: 11,
    });
  });
});
