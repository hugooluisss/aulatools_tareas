# Spec Delta

## Purpose

El administrador consulta tareas, entregas y comentarios como el profesor.

## ADDED Requirements

### Requirement: Admin sees tasks like a teacher
El administrador SHALL poder ver todas las materias de su escuela, las tareas de cada una con sus datos, el detalle de la tarea y las entregas por estudiante con estado y calificación, sin poder crear, editar, cancelar ni calificar desde la interfaz.

#### Scenario: Browse tasks
- **WHEN** el administrador abre Tareas y elige una materia
- **THEN** ve la lista de tareas de la materia y puede abrir el detalle y las entregas

#### Scenario: Read-only controls
- **WHEN** el administrador abre una materia, tarea o entrega
- **THEN** no aparecen los botones de crear, editar, cancelar ni calificar

### Requirement: Admin comments on deliveries
El administrador SHALL poder leer y agregar comentarios en cualquier entrega de su escuela, visibles para el estudiante y el profesor de la entrega.

#### Scenario: Add comment
- **WHEN** el administrador escribe un comentario en una entrega
- **THEN** el comentario aparece con su nombre y rol y lo ven el estudiante y el profesor
