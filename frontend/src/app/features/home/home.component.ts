import { Component, inject } from '@angular/core';
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
  count = 0;

  constructor() {
    if (this.role === 'student') {
      this.tasks.myTasks().subscribe((page) => (this.count = page.meta.total));
    } else {
      this.tasks.subjects().subscribe((page) => (this.count = page.meta.total));
    }
  }
}
