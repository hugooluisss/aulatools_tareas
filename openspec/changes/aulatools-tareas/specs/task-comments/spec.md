# Spec Delta

## Purpose

Permite comunicar al estudiante con el profesor (y el administrativo) mediante comentarios sobre una tarea, con visibilidad privada por estudiante.

## ADDED Requirements

### Requirement: Comments per task and student
Estudiante, profesor de la materia y administrativo SHALL poder agregar comentarios a una tarea. Cada comentario pertenece al hilo de un estudiante en esa tarea y registra autor, rol y fecha.

#### Scenario: Student comments
- **WHEN** un estudiante agrega un comentario en una de sus tareas
- **THEN** el comentario queda en su hilo con su nombre y rol

#### Scenario: Teacher replies
- **WHEN** el profesor responde en el hilo de un estudiante
- **THEN** el estudiante ve la respuesta

### Requirement: Comment visibility
Un hilo SHALL ser visible solo para el estudiante dueño, el profesor de la materia y el administrativo de la escuela. Otros estudiantes MUST NOT verlo.

#### Scenario: Other student
- **WHEN** un estudiante solicita comentarios de otro estudiante
- **THEN** el sistema responde 404

#### Scenario: Foreign teacher
- **WHEN** un profesor que no imparte la materia solicita el hilo
- **THEN** el sistema responde 403

### Requirement: Comment validation
El comentario MUST contener texto no vacío de hasta 2000 caracteres.

#### Scenario: Empty comment
- **WHEN** se envía un comentario vacío
- **THEN** el sistema rechaza la operación
