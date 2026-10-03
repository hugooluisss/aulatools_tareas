# Design

## Mapa de rutas del frontend

| Antes | Después |
|---|---|
| `registro-escuela` | `register-school` |
| `inicio` | `home` |
| `mis-tareas` (+ `/:deliveryId`) | `my-tasks` |
| `mis-materias` (+ `/:subjectId`) | `my-subjects` |
| `materias` (estudiante) | `subjects` |
| `tareas`, `tareas/:taskId/entregas` | `tasks`, `tasks/:taskId/deliveries` |
| `entregas/:deliveryId/comentarios` | `deliveries/:deliveryId/comments` |
| `estudiantes` / `profesores` | `students` / `teachers` |
| `ciclos` / `grupos` | `cycles` / `groups` |
| `admin/materias` | `admin/subjects` |
| `planes-estudio` | `study-plans` |
| `inscripciones` / `reinscripciones` | `enrollments` / `re-enrollments` |
| `escuela` | `school` |
| `cambiar-contrasena` | `change-password` |
| `calendario` / `avisos` | `calendar` / `announcements` |
| `login` | `login` (sin cambio) |

## Decisiones

1. Backend: solo `/inscriptions` → `/enrollments` (`/inscriptions/aspirants` → `/enrollments/aspirants`, `/inscriptions/reenrollable` → `/enrollments/reenrollable`, `/inscriptions/bulk` → `/enrollments/bulk`). Sin conflicto con rutas existentes. Los nombres de clases PHP no cambian.
2. Frontend: cambiar solo las cadenas de ruta; los textos visibles siguen en español. Buscar con `grep` todas las ocurrencias de cada ruta antigua (`app.routes.ts`, `rolesFor`, menú, `routerLink`, `navigate`, `redirectTo`, specs).
3. Sin redirecciones de URLs antiguas (aplicación interna, sin enlaces externos).
4. Si `change-password` u otras rutas viven en archivos `.bru` o docs, se actualizan igual.
