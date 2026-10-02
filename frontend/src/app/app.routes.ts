import { Routes } from '@angular/router';
import { roleGuard } from './core/guards/role.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () =>
      import('./features/auth/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: 'registro-escuela',
    loadComponent: () =>
      import('./features/auth/register-school/register-school.component').then(
        (m) => m.RegisterSchoolComponent,
      ),
  },
  { path: '', pathMatch: 'full', redirectTo: 'inicio' },
  {
    path: '',
    canActivate: [roleGuard],
    loadComponent: () => import('./core/layout/shell.component').then((m) => m.ShellComponent),
    children: [
      {
        path: 'mis-tareas',
        canActivate: [roleGuard],
        data: { roles: ['student'] },
        loadComponent: () =>
          import('./features/tasks/my-tasks/my-tasks.component').then((m) => m.MyTasksComponent),
      },
      {
        path: 'mis-tareas/:deliveryId',
        canActivate: [roleGuard],
        data: { roles: ['student'] },
        loadComponent: () =>
          import('./features/tasks/task-detail/task-detail.component').then(
            (m) => m.TaskDetailComponent,
          ),
      },
      {
        path: 'mis-materias',
        canActivate: [roleGuard],
        data: { roles: ['teacher'] },
        loadComponent: () =>
          import('./features/tasks/teacher-tasks/teacher-subjects.component').then(
            (m) => m.TeacherSubjectsComponent,
          ),
      },
      {
        path: 'mis-materias/:subjectId',
        canActivate: [roleGuard],
        data: { roles: ['teacher'] },
        loadComponent: () =>
          import('./features/tasks/teacher-tasks/teacher-subject-detail.component').then(
            (m) => m.TeacherSubjectDetailComponent,
          ),
      },
      { path: 'tareas', pathMatch: 'full', redirectTo: 'mis-materias' },
      {
        path: 'tareas/:taskId/entregas',
        canActivate: [roleGuard],
        data: { roles: ['teacher'] },
        loadComponent: () =>
          import('./features/tasks/task-deliveries/task-deliveries.component').then(
            (m) => m.TaskDeliveriesComponent,
          ),
      },
      {
        path: 'entregas/:deliveryId/comentarios',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher', 'student'] },
        loadComponent: () =>
          import('./features/tasks/delivery-comments/delivery-comments.component').then(
            (m) => m.DeliveryCommentsComponent,
          ),
      },
      ...[
        ['estudiantes', 'students'],
        ['profesores', 'teachers'],
        ['ciclos', 'cycles'],
        ['admin/materias', 'subjects'],
        ['grupos', 'groups'],
      ].map(([path, kind]) => ({
        path,
        canActivate: [roleGuard],
        data: { roles: ['admin'], kind },
        loadComponent: () =>
          import('./features/admin/admin-catalog/admin-catalog.component').then(
            (m) => m.AdminCatalogComponent,
          ),
      })),
      {
        path: 'inicio',
        loadComponent: () => import('./features/home/home.component').then((m) => m.HomeComponent),
      },
      {
        path: 'cambiar-contrasena',
        loadComponent: () =>
          import('./features/auth/change-password/change-password.component').then(
            (m) => m.ChangePasswordComponent,
          ),
      },
      {
        path: 'calendario',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher', 'student'] },
        loadComponent: () =>
          import('./features/calendar/calendar.component').then((m) => m.CalendarComponent),
      },
      {
        path: 'avisos',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher', 'student'] },
        loadComponent: () =>
          import('./features/announcements/announcements.component').then(
            (m) => m.AnnouncementsComponent,
          ),
      },
      ...[
        'mis-tareas',
        'materias',
        'mis-materias',
        'tareas',
        'estudiantes',
        'profesores',
        'ciclos',
        'grupos',
      ].map((path) => ({
        path,
        canActivate: [roleGuard],
        data: { roles: rolesFor(path) },
        loadComponent: () => import('./features/home/home.component').then((m) => m.HomeComponent),
      })),
    ],
  },
  { path: '**', redirectTo: 'inicio' },
];

function rolesFor(path: string): string[] {
  if (path === 'mis-tareas') return ['student'];
  if (path === 'mis-materias' || path === 'tareas') return ['teacher'];
  if (['estudiantes', 'profesores', 'ciclos', 'grupos'].includes(path)) return ['admin'];
  return ['admin', 'teacher', 'student'];
}
