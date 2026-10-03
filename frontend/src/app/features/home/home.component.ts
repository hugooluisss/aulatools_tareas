import { Component, inject, signal } from '@angular/core';
import { TokenStorageService } from '../../core/auth/token-storage.service';
import { TasksService } from '../tasks/tasks.service';

@Component({
  selector: 'app-home',
  standalone: true,
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss',
})
export class HomeComponent {
  private readonly tokens = inject(TokenStorageService);
  private readonly tasks = inject(TasksService);
  readonly role = this.tokens.getRole();
  count = signal(0);

  constructor() {
    if (this.role === 'student') {
      this.tasks
        .myTasks()
        .subscribe((response) => this.count.set(response.total));
    } else {
      this.tasks.subjects().subscribe((response) => this.count.set(response.total));
    }
  }
}
