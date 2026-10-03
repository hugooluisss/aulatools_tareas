# Design

## Context

Proyecto desde cero (repo vacío). Esta máquina no tiene php/composer/ng; sí tiene node, mysql local y Docker. Motivación y alcance en `proposal.md`; requisitos en `specs/`.

## Goals / Non-Goals

**Goals:**
- Monorepo `backend/` (Yii3 API REST JSON) y `frontend/` (Angular SPA), ejecutables con Docker Compose; MySQL en el host.
- Arquitectura por capas con responsabilidad única y un archivo por clase.
- BD en 3FN; `school_id` solo donde es necesario.
- Toda librería de terceros detrás de un servicio propio.

**Non-Goals:**
- Asistencias, calificación final por materia, archivos adjuntos, recuperación de contraseña por email, OAuth Google, i18n.

## Decisions

**Capas backend (por módulo en `backend/src/<Modulo>/`)**: `Controller` (HTTP: valida request, llama servicio, arma respuesta) → `Service` (reglas de negocio, transacciones, autorización por rol/escuela) → `Repository` (único que habla con la BD, PDO/Yii DB con consultas parametrizadas). Entidades/DTO en archivos separados. Un archivo por clase. *Alternativa*: Active Record en controllers; descartada por mezclar capas.

**Capas frontend (`frontend/src/app/`)**: cada componente en su carpeta con `x.component.ts` (controller), `.html` (view), `.scss`, `.spec.ts`. Servicios Angular (`*.service.ts`) llaman a la API; no hay HTTP en componentes. Standalone components, rutas con lazy loading por módulo y guards por rol.

**Auth**: JWT de acceso (HS256, expira en 8 h, claims: `sub`, `role`, `school_id`). Contraseñas con `password_hash` (Argon2id/bcrypt) detrás de `PasswordHasher`. Backend: `JwtService` envuelve la librería JWT (`firebase/php-jwt`). Frontend: `TokenStorageService` (localStorage) + interceptor HTTP. *Alternativa*: sesión por cookie; descartada por SPA separada.

**Aislamiento de librerías**: Frontend: `TokenStorageService`, `GoogleCalendarLinkService`, `ToastService`, `IconButtonComponent` + `AppTooltipDirective` (Bootstrap Icons y tooltip de Bootstrap) – ningún otro código importa esas librerías. Backend: `JwtService`, `PasswordHasher`. Cambiar la librería implica editar solo ese servicio.

**Modelo de datos (3FN)** — `school_id` solo en `users`, `academic_cycles`, `groups`, `announcements`, `calendar_events`; el resto lo hereda por FK:
- `schools(id, name)`
- `users(id, school_id, role[admin|teacher|student], email UNIQUE, password_hash, first_name, last_name)`
- `students(user_id PK/FK, enrollment_number, birth_date, status[active|inactive])`; la unicidad de matrícula por escuela se valida en el servicio (la escuela está en `users`).
- `academic_cycles(id, school_id, name, starts_on, ends_on, status[active|finished])`
- `subjects(id, cycle_id, teacher_id→users, name, status[in_progress|finished])`
- `groups(id, school_id, name)`; `group_subjects(group_id, subject_id)`
- `enrollments(student_id, subject_id, source_group_id NULL, PK(student_id,subject_id))`
- `tasks(id, subject_id, name, description, due_at DATE, status[active|cancelled])`
- `task_deliveries(id, task_id, student_id, status[pending|delivered|graded|cancelled], delivered_at NULL, grade NULL, UNIQUE(task_id,student_id))`
- `task_comments(id, delivery_id, author_id→users, body, created_at)` — el hilo es la entrega, lo que da la privacidad por estudiante.
- `calendar_events(id, school_id, subject_id NULL, title, description, starts_at, ends_at)`
- `announcements(id, school_id, title, body, starts_on, ends_on)`
La escuela de una materia/tarea/entrega se obtiene por JOIN (subject→cycle→school); el servicio valida pertenencia en cada operación. "Vencida" y "A tiempo" se calculan en consulta, no se almacenan.

**Migraciones**: migraciones de Yii3 versionadas; sin seeds de datos reales, solo datos de demo bajo comando explícito.

**Docker**: `docker-compose.yml` con `backend` (php 8.3 + composer, `php -S`/RoadRunner dev), `frontend` (node, `ng serve`); la BD es el MySQL del host vía `host.docker.internal`. Credenciales en `.env` (no versionado; `.env.example` sí).

**UI**: Bootstrap 5 vía Sass con variables de paleta (`#5B5694`, `#5BA25F`, `#F5C242`, `#E8A598`, fondo lavanda) en `styles/_variables.scss`; íconos Bootstrap Icons.

**Pruebas**: backend PHPUnit para servicios (con repositorios simulados) y pruebas de integración de endpoints clave; frontend `*.spec.ts` por componente/servicio.

## Risks / Trade-offs

- [Yii3 aún con API cambiante] → fijar versiones en `composer.lock`; capas propias reducen acoplamiento.
- [Aislamiento multi-escuela por servicio, no por BD] → cada Service valida `school_id` del JWT; pruebas de acceso cruzado obligatorias.
- [JWT en localStorage expuesto a XSS] → expiración corta, sin HTML crudo en vistas; migrable a cookie httpOnly cambiando `TokenStorageService` + backend.
- [Docker en dev necesita acceso al MySQL del host] → `bind-address` y usuario con host permitido; se resuelve al orquestar con credenciales del usuario.
- [Unicidad de matrícula por escuela no expresable como índice único sin duplicar `school_id`] → validación en servicio dentro de transacción (consulta con bloqueo); se acepta el trade-off para mantener 3FN.

## Open Questions

- Credenciales y nombre de BD del MySQL local (pendientes hasta la orquestación).
