import { DatePipe } from '@angular/common';
import { Component, inject, input, output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { StudentNote, StudentNotesService } from '../student-notes.service';
import { Page } from '../../../core/models/page';
import { PaginatorComponent } from '../../../shared/paginator/paginator.component';

@Component({
  selector: 'app-student-notes-modal',
  standalone: true,
  imports: [DatePipe, FormsModule, PaginatorComponent],
  templateUrl: './student-notes-modal.component.html',
})
export class StudentNotesModalComponent {
  private readonly api = inject(StudentNotesService);
  studentId = input.required<number>();
  closed = output<void>();
  notes = signal<Page<StudentNote>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
  body = signal('');
  loading = signal(true);
  saving = signal(false);
  error = signal('');

  ngOnInit(): void {
    this.load();
  }

  load(page = this.page()): void {
    this.page.set(page);
    this.loading.set(true);
    this.api.list(this.studentId(), page).subscribe({
      next: (notes) => {
        this.notes.set(notes);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('No se pudieron cargar las notas.');
        this.loading.set(false);
      },
    });
  }

  add(): void {
    const body = this.body().trim();
    if (!body || body.length > 2000) return;
    this.saving.set(true);
    this.api.add(this.studentId(), body).subscribe({
      next: (note) => {
        this.body.set('');
        this.saving.set(false);
        this.load();
      },
      error: () => {
        this.error.set('No se pudo guardar la nota.');
        this.saving.set(false);
      },
    });
  }
}
