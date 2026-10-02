import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TasksService, Subject } from '../tasks.service';

@Component({
  selector: 'app-teacher-subjects',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './teacher-subjects.component.html',
  styleUrl: './teacher-subjects.component.scss',
})
export class TeacherSubjectsComponent {
  private readonly tasks = inject(TasksService);
  subjects = signal<Subject[]>([]);

  constructor() {
    this.tasks.subjects().subscribe((rows) => this.subjects.set(rows));
  }
}
