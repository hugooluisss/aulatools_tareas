# Design

## Context

Estado actual:

- `subjects` tiene `cycle_id` (FK a `academic_cycles`), `teacher_id`, `name` y `status` (`in_progress`/`finished`); no tiene clave ni plan.
- `enrollments` es `(student_id, subject_id)` con `source_group_id`; `tasks` cuelga solo de `subject_id`; `task_deliveries` de `task_id` y `student_id`; `calendar_events.subject_id` es opcional.
- `groups` tiene `cycle_id` y sus materias (`group_subjects`) deben ser del ciclo del grupo; `inscriptions` (cambio `student-cycle-enrollment`, ya implementado y sin archivar) liga estudiante, ciclo y grupo y reutiliza `EnrollmentService::enrollCycleGroup`.
- Contrato de API: respuestas sin `data`, paginación en headers `X-*`, errores con HTTP y `{"error":{"code","message"}}` vía `App\Shared\JsonResponse`. Frontend Angular 22 zoneless con signals; la API devuelve ids como texto.
- Entorno de desarrollo: los datos se pueden reiniciar (indicado por el usuario).

## Goals / Non-Goals

**Goals:**
- Materias como catálogo reutilizable entre ciclos, con clave única, plan opcional y estado activo/inactivo.
- Inscripciones a materia y tareas con ciclo, sin mezclar ciclos.
- Catálogo de planes de estudio con su pantalla.

**Non-Goals:**
- Que los grupos dependan de un plan.
- Profesor distinto por ciclo (el profesor sigue en la materia; se documenta como límite).
- Migrar los datos actuales de materias, tareas o inscripciones a materia: se reinician en desarrollo.
- Calificaciones, aprobado/reprobado o historial académico.

## Decisions

1. **Migración nueva con reinicio de desarrollo.** Una migración nueva (sin editar las anteriores): borra las filas que dependen de materias (`task_comments`, `task_deliveries`, `tasks`, `calendar_events` con materia, `enrollments`, `group_subjects`, `subjects`), crea `study_plans`, altera `subjects` (quita `cycle_id` y su FK/índice, agrega `clave VARCHAR(40) NOT NULL`, `plan_id NULL` con FK, `status ENUM('active','inactive') DEFAULT 'active'`, `UNIQUE (school_id, clave)`), y agrega `cycle_id NOT NULL` con FK a `enrollments` (PK `(student_id, subject_id, cycle_id)`) y a `tasks`. `subjects` no tiene `school_id`: se agrega `school_id NOT NULL` con FK para la unicidad de la clave. El `down` reconstruye el esquema anterior sin restaurar datos. Se documenta como solo para desarrollo.
   *Alternativa:* conservar y rellenar datos (ciclo desde `subjects.cycle_id`). Se descartó: el usuario pidió borrar y recrear.
2. **Planes** (`study_plans`: `id`, `school_id`, `clave`, `name`, `status`, `UNIQUE (school_id, clave)`). Módulo `StudyPlans` (controlador, servicio, repositorio) con `GET/POST /study-plans`, `GET/PUT/DELETE /study-plans/{id}`; borrar con materias → 409; desactivar con `PUT` (`status`). Solo administrador.
3. **Materias.** `POST/PUT /subjects` reciben `clave`, `name`, `teacher_id`, `plan_id` (opcional, plan activo, de la escuela, 422 si inactivo) y `status` en edición. Clave duplicada → 409 (por `UNIQUE`, capturando `IntegrityException`). `GET /subjects` devuelve `clave`, `plan_id`, `plan_name`, `status`, `students_count` (conteo de estudiantes distintos con inscripción a la materia, una consulta) y acepta `status=active`. Las listas de selección (grupos, modal) piden solo activas.
4. **Grupos y sincronización.** Se elimina la validación "materias del ciclo"; solo se exige que cada materia exista en la escuela y esté activa (400 si no). Al actualizar las materias de un grupo, dentro de la misma transacción: para cada estudiante con inscripción en ese grupo se inscriben las materias nuevas (`enrollCycleGroup` con el ciclo del grupo) y se borran las filas `enrollments` con `source_group_id = grupo` y materia retirada. Se resuelve por lote (INSERT … SELECT / DELETE con IN), sin consultas por estudiante.
5. **Inscripciones a materia con ciclo.** `EnrollmentRepository::enroll*` y `unenroll*` reciben `cycle_id`; `enrollCycleGroup` usa el ciclo del grupo. `POST /subjects/{id}/students/bulk` recibe `{cycle_id, student_ids}` y valida: ciclo de la escuela y activo (422), estudiantes de la escuela (404), inscripción del estudiante en ese ciclo (400), ya inscritos en (materia, ciclo) (409). `GET /subjects/{id}/students?cycle_id=` filtra por ciclo; sin parámetro devuelve los de ciclos activos. `DELETE /subjects/{id}/students/{student_id}` recibe `cycle_id` por query.
6. **Tareas con ciclo.** `tasks.cycle_id NOT NULL`; `POST /tasks` recibe `cycle_id` (activo); las entregas se generan para los estudiantes inscritos en (materia, ciclo) — `TaskDeliveryEnrollmentHook` y su adaptador filtran por ciclo. Listados de tareas aceptan `cycle_id`, por defecto ciclos activos. Entregas, comentarios y "mis tareas" no cambian de forma: ya cuelgan de una entrega, que pertenece a una tarea con ciclo. Al inscribir a un estudiante en una materia/ciclo se generan sus entregas de las tareas existentes de ese ciclo.
7. **Reinscribible.** La consulta de `GET /inscriptions/reenrollable` pasa a: estudiante activo, con inscripción más reciente en ciclo `finished` y `NOT EXISTS` inscripción en ciclo activo. La validación de `POST /inscriptions/bulk` con `type = reenrollment` usa la misma consulta (se elimina la subconsulta de materias no finalizadas).
8. **Calendario.** `calendar_events.subject_id` se conserva; la visibilidad para estudiantes pasa por `enrollments` de ciclos activos y para el profesor por `subjects.teacher_id`.
9. **Frontend.** Formulario de materias: Nombre, Clave, Plan (opcional, solo planes activos), Profesor y Estado (en edición), sin ciclo; tabla con Clave, Plan, Estado, estudiantes y acciones con deshabilitado (no oculto). Formulario de grupos: ciclo y materias activas del catálogo. Pantalla "Planes de estudio" con tabla y modal. Modal "Agregar estudiante": selector de ciclo activo (preseleccionado si hay uno solo), tabla de candidatos de `GET /inscriptions?cycle_id=` menos los de `GET /subjects/{id}/students?cycle_id=`. "Ver estudiantes" con selector de ciclo. Pantallas de profesor/estudiante de tareas: selector de ciclo cuando hay más de un ciclo activo.
10. **Datos de desarrollo.** Tras la migración, con Bruno: borrar los ciclos duplicados 12 y 13 (SQL puntual), crear un plan, crear materias nuevas con clave y plan (y una sin plan), actualizar el grupo 11 con sus materias (la sincronización inscribe a los 5 estudiantes ya inscritos).

## Risks / Trade-offs

- [Reinicio destructivo de datos dependientes de materias] → solo desarrollo; se documenta en la migración y no hay `down` que restaure datos.
- [Profesor fijo por materia] → una materia no puede tener otro profesor en otro ciclo; se acepta como límite actual.
- [Refactor amplio en Tasks/TaskComments/Calendar] → cambios acotados a filtros por ciclo; se verifica cada módulo con su test y una llamada.
- [Varios ciclos activos] → selectores de ciclo en modal y pantallas; con uno solo se preselecciona.
- [Sincronización de grupos borra filas de materias retiradas] → solo filas originadas por el grupo, no las individuales.

## Migration Plan

1. Aplicar la migración nueva en desarrollo y registrarla en la tabla `migration`.
2. Desplegar backend y luego frontend (el frontend viejo envía `cycle_id` en materias y falla con 400; se despliegan juntos).
3. Reiniciar datos de desarrollo con la secuencia de la decisión 10.
4. Rollback: `down` recrea el esquema anterior sin datos.

## Open Questions

- ¿Debe poder una materia tener un profesor distinto por ciclo? No bloquea este cambio.
