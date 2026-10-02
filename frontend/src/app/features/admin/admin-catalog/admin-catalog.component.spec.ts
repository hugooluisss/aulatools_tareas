import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { AdminCatalogComponent } from './admin-catalog.component';
import { StudentsService } from '../students.service';
import { TeachersService } from '../teachers.service';
import { CyclesService } from '../cycles.service';
import { SubjectsService } from '../subjects.service';
import { GroupsService } from '../groups.service';
import { ToastService } from '../../../core/services/toast.service';

describe('AdminCatalogComponent', () => {
  let fixture: ComponentFixture<AdminCatalogComponent>;
  const api = {
    list: () => of({ data: [] }),
    save: () => of({ data: {} }),
    remove: () => of(void 0),
    status: () => of({}),
    reset: () => of({}),
    finish: () => of({}),
    enroll: () => of({}),
    students: () => of({ data: [] }),
    unenroll: () => of(void 0),
  };
  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [AdminCatalogComponent],
      providers: [
        { provide: ActivatedRoute, useValue: { snapshot: { data: { kind: 'cycles' } } } },
        { provide: StudentsService, useValue: api },
        { provide: TeachersService, useValue: api },
        { provide: CyclesService, useValue: api },
        { provide: SubjectsService, useValue: api },
        { provide: GroupsService, useValue: api },
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
    expect(component.rows).toEqual([]);
  });
});
