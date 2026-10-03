import { DatePipe } from '@angular/common';
import { Component, inject, input, output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { StudentNote, StudentNotesService } from '../student-notes.service';

@Component({
  selector: 'app-student-notes-modal',
  standalone: true,
  imports: [DatePipe, FormsModule],
  templateUrl: './student-notes-modal.component.html',
})
export class StudentNotesModalComponent {
  private readonly api = inject(StudentNotesService);
  studentId = input.required<number>();
  closed = output<void>();
  notes = signal<StudentNote[]>([]);
  body = signal('');
  loading = signal(true);
  saving = signal(false);
  error = signal('');

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.list(this.studentId()).subscribe({
      next: (result) => {
        this.notes.set(result.data);
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
        this.notes.update((notes) => [note, ...notes]);
        this.body.set('');
        this.saving.set(false);
      },
      error: () => {
        this.error.set('No se pudo guardar la nota.');
        this.saving.set(false);
      },
    });
  }
}
