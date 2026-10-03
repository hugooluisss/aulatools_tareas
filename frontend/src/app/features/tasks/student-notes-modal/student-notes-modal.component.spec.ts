import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { StudentNotesService } from '../student-notes.service';
import { StudentNotesModalComponent } from './student-notes-modal.component';

describe('StudentNotesModalComponent', () => {
  it('loads notes and adds the new note to the history', () => {
    const api = {
      list: vi.fn().mockReturnValue(of({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 })),
      add: (_id: number, body: string) =>
        of({
          id: 3,
          student_id: 7,
          author: { id: 1, first_name: 'Ana', last_name: 'Ríos', role: 'admin' },
          body,
          created_at: '2026-10-02',
        }),
    };
    TestBed.configureTestingModule({
      imports: [StudentNotesModalComponent],
      providers: [{ provide: StudentNotesService, useValue: api }],
    });
    const fixture = TestBed.createComponent(StudentNotesModalComponent);
    fixture.componentRef.setInput('studentId', 7);
    const component = fixture.componentInstance;
    fixture.detectChanges();
    component.body.set('Seguimiento');
    component.add();
    expect(component.notes().items).toEqual([]);
    expect(api.list).toHaveBeenCalledTimes(2);
  });
});
