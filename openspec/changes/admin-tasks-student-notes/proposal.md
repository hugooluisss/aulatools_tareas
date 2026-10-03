# Proposal

## Why

El administrador no tiene pantallas para las tareas: las rutas de tareas, entregas y materias del profesor son solo para profesores, aunque el backend ya permite al administrador verlas, comentarlas y gestionarlas. Además falta un lugar para que administración y profesores dejen notas sobre un estudiante que el estudiante no ve.

## What Changes

- **Tareas para el administrador**: igual que el profesor, el administrador ve las materias de la escuela, la lista de tareas de cada materia con sus datos (título, fecha de entrega, estado, ciclo), el detalle de la tarea, las entregas por estudiante (estado y calificación) y los comentarios de cada entrega, y puede agregar comentarios. Se reutilizan los componentes del profesor; el administrador queda en solo lectura salvo comentar (sin crear, editar, cancelar ni calificar).
- **Comentarios de tarea** (ya existentes, por entrega): siguen visibles para estudiante, profesor y administrador.
- **Notas del estudiante** (nuevas): control administrativo, ligadas al estudiante sin tarea. Las ven y agregan el administrador y los profesores del estudiante; el estudiante no las ve. Nueva tabla `student_notes` y endpoints `GET` y `POST /users/students/{id}/notes`.
- Frontend: opción "Notas" por estudiante (fila del catálogo de estudiantes y lista de entregas de una tarea) que abre un modal con el historial y el formulario para agregar.
- Menú: el administrador tiene la entrada "Tareas".

## Capabilities

### New Capabilities
- `admin-task-oversight`: el administrador consulta tareas, entregas y comentarios y comenta.
- `student-notes`: notas administrativas por estudiante.

### Modified Capabilities

(Ninguna.)

## Impact

- **Base de datos**: migración nueva (`student_notes`).
- **Backend**: módulo `StudentNotes` (controlador, servicio, repositorio), rutas, tests; ajustes de acceso del administrador solo si falta alguno.
- **Bruno**: requests de notas.
- **Frontend**: rutas y roles del administrador, menú, modal de notas, servicio.
