# Proposal

## Why

Hoy el profesor solo existe en la materia (`subjects.teacher_id`): todos los estudiantes de una materia comparten profesor. En la práctica cada grupo tiene un profesor titular y, para algunos estudiantes, el profesor de una materia es otro. Falta registrar quién imparte cada materia a cada inscripción y poder corregirlo en bloque.

## What Changes

- `groups` recibe `teacher_id` (profesor por defecto del grupo, opcional, debe ser un profesor de la misma escuela).
- `enrollment_subject_bindings` recibe `teacher_id`. Al crear vínculos (inscripción individual, masiva, reinscripción, cambio de grupo, materia agregada al grupo o materia agregada a un estudiante) el vínculo toma `COALESCE(groups.teacher_id, subjects.teacher_id)` del grupo de la inscripción.
- Nuevo endpoint de administrador `PUT /subjects/{id}/students/teacher` con `cycle_id`, `student_ids` y `teacher_id`: cambia el profesor de esos vínculos en una sola sentencia.
- Cambiar el profesor por defecto del grupo **no** modifica vínculos existentes.
- Las respuestas de grupo y de estudiantes de una materia exponen `teacher_id`.
- Frontend mínimo: selector de profesor en el formulario de grupo.
- Migración que conserva datos: los vínculos existentes toman `subjects.teacher_id`; los grupos existentes quedan sin profesor.
- No cambia: la autorización basada en `subjects.teacher_id` (listado de materias, tareas, comentarios).

## Capabilities

### New Capabilities
- `binding-teachers`: profesor por defecto del grupo, profesor por vínculo materia-inscripción y reasignación masiva.

### Modified Capabilities

(Ninguna: los specs principales aún no existen.)

## Impact

- **Base de datos**: migración nueva (`groups.teacher_id`, `enrollment_subject_bindings.teacher_id`, FK a `users`, índice).
- **Backend**: `Groups`, `Enrollments` (5 puntos de inserción de vínculos), `Subjects` (endpoint y listado), `config/common/routes.php`; tests.
- **Bruno**: request nueva y campo `teacher_id` en grupos.
- **Frontend**: servicio y formulario de grupos.
