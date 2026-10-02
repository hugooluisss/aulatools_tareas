import { Component, inject, signal } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
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
  schoolName = signal('');
  constructor() {
    this.school.get().subscribe((r) => this.schoolName.set(r.name));
  }
  menu = [
    { label: 'Inicio', path: '/inicio', roles: ['admin', 'teacher', 'student'] },
    { label: 'Mis tareas', path: '/mis-tareas', roles: ['student'] },
    { label: 'Materias', path: '/materias', roles: ['student'] },
    { label: 'Mis materias', path: '/mis-materias', roles: ['teacher'] },
    { label: 'Tareas', path: '/tareas', roles: ['teacher'] },
    { label: 'Estudiantes', path: '/estudiantes', roles: ['admin'] },
    { label: 'Profesores', path: '/profesores', roles: ['admin'] },
    { label: 'Ciclos', path: '/ciclos', roles: ['admin'] },
    { label: 'Grupos', path: '/grupos', roles: ['admin'] },
    { label: 'Materias', path: '/admin/materias', roles: ['admin'] },
    { label: 'Escuela', path: '/escuela', roles: ['admin'] },
    { label: 'Calendario', path: '/calendario', roles: ['admin', 'teacher', 'student'] },
    { label: 'Avisos', path: '/avisos', roles: ['admin', 'teacher', 'student'] },
  ];
  get visibleMenu() {
    const role = this.tokens.getRole();
    return this.menu.filter((item) => item.roles.includes(role ?? ''));
  }

  trackMenuItem(item: { path: string }): string {
    return item.path;
  }
}
