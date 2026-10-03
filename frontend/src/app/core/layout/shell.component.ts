import { Component, computed, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { toSignal } from '@angular/core/rxjs-interop';
import { filter, map, startWith } from 'rxjs';
import { SchoolService } from '../../features/admin/school.service';
import { TokenStorageService } from '../auth/token-storage.service';

@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './shell.component.html',
  styleUrl: './shell.component.scss',
})
export class ShellComponent {
  private readonly tokens = inject(TokenStorageService);
  private readonly school = inject(SchoolService);
  private readonly router = inject(Router);
  schoolName = signal('');
  controlEscolarOpen = signal(false);
  catalogosOpen = signal(false);
  reportesOpen = signal(false);
  escuelaOpen = signal(false);
  constructor() {
    this.school.get().subscribe((r) => this.schoolName.set(r.name));
  }
  menu = [
    { label: 'Mis tareas', path: '/my-tasks', roles: ['student'] },
    { label: 'Materias', path: '/subjects', roles: ['student'] },
    { label: 'Mis materias', path: '/my-subjects', roles: ['teacher'] },
    { label: 'Tareas', path: '/tasks', roles: ['teacher'] },
    { label: 'Tareas', path: '/admin/tasks', roles: ['admin'] },
    { label: 'Calendario', path: '/calendar', roles: ['teacher', 'student'] },
    { label: 'Avisos', path: '/announcements', roles: ['teacher', 'student'] },
  ];
  controlEscolar = [
    { label: 'Estudiantes', path: '/students' },
    { label: 'Inscripciones', path: '/enrollments' },
    { label: 'Reinscripciones', path: '/re-enrollments' },
  ];
  catalogos = [
    { label: 'Ciclos', path: '/cycles' },
    { label: 'Grupos', path: '/groups' },
    { label: 'Materias', path: '/admin/subjects' },
    { label: 'Planes de estudio', path: '/study-plans' },
  ];
  reportes = [{ label: 'Lista de asistencia', path: '/reports/attendance' }];
  escuela = [
    { label: 'Calendario', path: '/calendar' },
    { label: 'Avisos', path: '/announcements' },
    { label: 'Profesores', path: '/teachers' },
    { label: 'Usuarios', path: '/admin-users' },
    { label: 'Generales', path: '/school' },
  ];
  private readonly currentUrl = toSignal(
    this.router.events.pipe(
      filter((event): event is NavigationEnd => event instanceof NavigationEnd),
      map((event) => event.urlAfterRedirects),
      startWith(this.router.url),
    ),
    { initialValue: this.router.url },
  );
  isAdmin = computed(() => this.tokens.getRole() === 'admin');
  controlEscolarActive = computed(() =>
    this.controlEscolar.some((item) => this.currentUrl().startsWith(item.path)),
  );
  catalogosActive = computed(() =>
    this.catalogos.some((item) => this.currentUrl().startsWith(item.path)),
  );
  reportesActive = computed(() =>
    this.reportes.some((item) => this.currentUrl().startsWith(item.path)),
  );
  escuelaActive = computed(() =>
    this.escuela.some((item) => this.currentUrl().startsWith(item.path)),
  );
  get visibleMenu() {
    const role = this.tokens.getRole();
    return this.menu.filter((item) => item.roles.includes(role ?? ''));
  }

  trackMenuItem(item: { path: string }): string {
    return item.path;
  }
}
