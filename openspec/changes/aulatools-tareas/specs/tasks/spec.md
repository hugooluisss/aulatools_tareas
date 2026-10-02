# Spec Delta

## Purpose

Define las tareas por materia, la entrega individual de cada estudiante, sus estados y calificaciones y los listados de consulta.

## ADDED Requirements

### Requirement: Task definition
El profesor de una materia (y el administrativo) SHALL poder crear, editar y cancelar tareas con nombre, descripción y fecha de vencimiento. Una tarea pertenece a una materia.

#### Scenario: Create task
- **WHEN** el profesor crea una tarea en una de sus materias
- **THEN** se genera una entrega en estado Pendiente por cada estudiante inscrito

#### Scenario: Task in foreign subject
- **WHEN** un profesor crea una tarea en una materia que no es suya
- **THEN** el sistema responde 403

### Requirement: Per-student delivery state
Cada estudiante inscrito SHALL tener una entrega por tarea con estado Pendiente, Entregada, Calificada o Cancelada, fecha de entrega y calificación (0-100). El estudiante MUST NOT modificar el estado; solo el profesor lo marca.

#### Scenario: Mark delivered
- **WHEN** el profesor marca la entrega de un estudiante como Entregada
- **THEN** se guarda la fecha de entrega y el estado cambia a Entregada

#### Scenario: Grade
- **WHEN** el profesor registra una calificación entre 0 y 100 para una entrega Entregada
- **THEN** el estado pasa a Calificada y la calificación se guarda

#### Scenario: Invalid grade
- **WHEN** la calificación está fuera de 0-100
- **THEN** el sistema rechaza la operación

#### Scenario: Student tries to update
- **WHEN** un estudiante intenta cambiar estado o calificación
- **THEN** el sistema responde 403

### Requirement: Cancel whole task
Al cancelar una tarea el sistema SHALL poner en Cancelada todas sus entregas.

#### Scenario: Cancel task
- **WHEN** el profesor cancela una tarea con entregas Pendiente y Entregada
- **THEN** todas las entregas quedan Cancelada

### Requirement: Derived timeliness
El sistema SHALL calcular, sin almacenarlo, el indicador "Vencida" (sin entregar y con fecha de vencimiento pasada) y "A tiempo" (entregada con fecha de entrega menor o igual al vencimiento).

#### Scenario: Overdue
- **WHEN** una entrega Pendiente tiene vencimiento pasado
- **THEN** se muestra como Vencida

#### Scenario: On time
- **WHEN** una entrega se marca Entregada antes o en la fecha de vencimiento
- **THEN** se muestra como A tiempo

### Requirement: My tasks list
El estudiante SHALL ver "Mis tareas" con todas sus entregas mostrando nombre, materia, calificación y estado, con filtro por estado cuyo valor por defecto es Pendiente.

#### Scenario: Default filter
- **WHEN** el estudiante abre Mis tareas
- **THEN** ve las tareas Pendiente y puede cambiar el filtro a otro estado

#### Scenario: Filter by state
- **WHEN** el estudiante filtra por Calificada
- **THEN** solo ve entregas Calificada

### Requirement: Task detail
El detalle de una tarea del estudiante SHALL mostrar nombre, descripción, nombre del profesor, fecha de vencimiento, calificación, fecha de entrega y estado.

#### Scenario: Open detail
- **WHEN** el estudiante abre una tarea de su lista
- **THEN** ve todos los campos indicados

#### Scenario: Another student's task
- **WHEN** un estudiante solicita la entrega de otro estudiante
- **THEN** el sistema responde 404

### Requirement: Teacher task list
El profesor SHALL ver las tareas de sus materias y, por tarea, la lista de estudiantes con su estado y calificación, con filtro por estado.

#### Scenario: Teacher views deliveries
- **WHEN** el profesor abre una tarea
- **THEN** ve una fila por estudiante inscrito
