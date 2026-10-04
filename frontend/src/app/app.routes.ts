import { Routes } from '@angular/router';
import { roleGuard } from './core/guards/role.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () =>
      import('./features/auth/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: 'forgot-password',
    loadComponent: () =>
      import('./features/auth/forgot-password/forgot-password.component').then(
        (m) => m.ForgotPasswordComponent,
      ),
  },
  {
    path: 'reset-password',
    loadComponent: () =>
      import('./features/auth/reset-password/reset-password.component').then(
        (m) => m.ResetPasswordComponent,
      ),
  },
  {
    path: 'register-school',
    loadComponent: () =>
      import('./features/auth/register-school/register-school.component').then(
        (m) => m.RegisterSchoolComponent,
      ),
  },
  { path: '', pathMatch: 'full', redirectTo: 'home' },
  {
    path: '',
    canActivate: [roleGuard],
    loadComponent: () => import('./core/layout/shell.component').then((m) => m.ShellComponent),
    children: [
      {
        path: 'my-tasks',
        canActivate: [roleGuard],
        data: { roles: ['student'] },
        loadComponent: () =>
          import('./features/tasks/my-tasks/my-tasks.component').then((m) => m.MyTasksComponent),
      },
      {
        path: 'my-tasks/:deliveryId',
        canActivate: [roleGuard],
        data: { roles: ['student'] },
        loadComponent: () =>
          import('./features/tasks/task-detail/task-detail.component').then(
            (m) => m.TaskDetailComponent,
          ),
      },
      {
        path: 'my-subjects',
        canActivate: [roleGuard],
        data: { roles: ['teacher'] },
        loadComponent: () =>
          import('./features/tasks/teacher-tasks/teacher-subjects.component').then(
            (m) => m.TeacherSubjectsComponent,
          ),
      },
      {
        path: 'admin/tasks',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/tasks/admin-task-overview/admin-task-overview.component').then(
            (m) => m.AdminTaskOverviewComponent,
          ),
      },
      {
        path: 'admin/tasks/:subjectId',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/tasks/teacher-tasks/teacher-subject-detail.component').then(
            (m) => m.TeacherSubjectDetailComponent,
          ),
      },
      {
        path: 'my-subjects/:subjectId',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher'] },
        loadComponent: () =>
          import('./features/tasks/teacher-tasks/teacher-subject-detail.component').then(
            (m) => m.TeacherSubjectDetailComponent,
          ),
      },
      {
        path: 'tasks/:taskId/deliveries',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher'] },
        loadComponent: () =>
          import('./features/tasks/task-deliveries/task-deliveries.component').then(
            (m) => m.TaskDeliveriesComponent,
          ),
      },
      ...[
        ['students', 'students'],
        ['teachers', 'teachers'],
        ['admin-users', 'admins'],
        ['cycles', 'cycles'],
        ['admin/subjects', 'subjects'],
        ['groups', 'groups'],
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
        path: 'study-plans',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/admin/study-plans/study-plans.component').then(
            (m) => m.StudyPlansComponent,
          ),
      },
      {
        path: 'reports/attendance',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher'] },
        loadComponent: () =>
          import('./features/admin/attendance-report/attendance-report.component').then(
            (m) => m.AttendanceReportComponent,
          ),
      },
      {
        path: 'reports/task-report-card',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher'] },
        loadComponent: () =>
          import('./features/admin/task-report-card/task-report-card.component').then(
            (m) => m.TaskReportCardComponent,
          ),
      },
      ...[
        ['enrollments', 'enrollment'],
        ['re-enrollments', 'reenrollment'],
      ].map(([path, type]) => ({
        path,
        canActivate: [roleGuard],
        data: { roles: ['admin'], type },
        loadComponent: () =>
          import('./features/admin/student-cycle-enrollment/student-cycle-enrollment.component').then(
            (m) => m.StudentCycleEnrollmentComponent,
          ),
      })),
      {
        path: 'school',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/admin/school/school.component').then((m) => m.SchoolComponent),
      },
      {
        path: 'home',
        loadComponent: () => import('./features/home/home.component').then((m) => m.HomeComponent),
      },
      {
        path: 'change-password',
        loadComponent: () =>
          import('./features/auth/change-password/change-password.component').then(
            (m) => m.ChangePasswordComponent,
          ),
      },
      {
        path: 'calendar',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher', 'student'] },
        loadComponent: () =>
          import('./features/calendar/calendar.component').then((m) => m.CalendarComponent),
      },
      {
        path: 'announcements',
        canActivate: [roleGuard],
        data: { roles: ['admin', 'teacher', 'student'] },
        loadComponent: () =>
          import('./features/announcements/announcements.component').then(
            (m) => m.AnnouncementsComponent,
          ),
      },
      ...['my-tasks', 'subjects', 'my-subjects', 'students', 'teachers', 'cycles', 'groups'].map(
        (path) => ({
          path,
          canActivate: [roleGuard],
          data: { roles: rolesFor(path) },
          loadComponent: () =>
            import('./features/home/home.component').then((m) => m.HomeComponent),
        }),
      ),
    ],
  },
  { path: '**', redirectTo: 'home' },
];

function rolesFor(path: string): string[] {
  if (path === 'my-tasks') return ['student'];
  if (path === 'my-subjects') return ['teacher'];
  if (['students', 'teachers', 'cycles', 'groups'].includes(path)) return ['admin'];
  return ['admin', 'teacher', 'student'];
}
