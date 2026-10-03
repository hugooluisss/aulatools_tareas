# Design

## Decisiones

1. **Administrador en solo lectura (más comentar)**: el backend ya permite al administrador crear, editar, cancelar y calificar, pero la interfaz del administrador solo muestra consulta y comentarios; botones de crear, editar, cancelar y calificar se ocultan con `isAdmin`. No se restringe el backend.
2. **Reutilización de pantallas**: rutas nuevas `admin/tasks` (lista de materias; reutiliza el componente de materias del profesor con datos de `GET /subjects`) y `admin/tasks/:subjectId` (reutiliza el detalle de materia con `GET /subjects/{id}/tasks`). Las rutas existentes `tasks/:taskId/deliveries` y `deliveries/:deliveryId/comments` se abren también al rol `admin` (el comentario ya lo permite). Menú: entrada "Tareas" para admin hacia `/admin/tasks`. Antes de reutilizar, confirmar que los componentes del profesor no asumen `GET /me…` ni el usuario como profesor.
3. **Tabla `student_notes`**: `id`, `student_id` (FK `students(user_id)` ON DELETE CASCADE), `author_id` (FK `users(id)`), `body VARCHAR(2000)` NOT NULL con el mismo CHECK de `task_comments`, `created_at`, índice `(student_id, created_at)`. Migración `M26100500000000CreateStudentNotes` con `down`.
4. **Acceso a notas**: admin de la misma escuela siempre; profesor solo si el estudiante tiene al menos un vínculo (`enrollment_subject_bindings`) cuyo `teacher_id` es el profesor o cuya materia tiene `subjects.teacher_id` igual al profesor; estudiante: 404. Estudiante de otra escuela: 404. Cuerpo de 1 a 2000 caracteres tras `trim`, error 400 en caso contrario. Lista ordenada por fecha descendente, paginada como `CommentService` (`page`, `per_page` ≤ 100) con autor (id, nombre, rol). Sin editar ni borrar notas en este alcance.
5. **Modal de notas**: componente `student-notes-modal` con el patrón de modal Bootstrap del proyecto y signals; se abre desde la fila del estudiante (administrador) y desde la lista de entregas (administrador y profesor).

## Riesgos

- Un profesor ve las notas escritas por otros profesores y por administración sobre ese estudiante; es lo pedido.
