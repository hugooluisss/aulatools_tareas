# Spec Delta

## Purpose

Orientar a docentes, administrativos y estudiantes dentro del flujo de tareas con navegación accesible hacia las pantallas anteriores.

## ADDED Requirements

### Requirement: Breadcrumb navigation in task flows
Las pantallas del flujo SHALL mostrar migajas con enlaces a los niveles anteriores y el elemento actual como último elemento, sin enlace y con `aria-current`. El control SHALL ser una navegación accesible con nombre «Migas de pan» y una lista ordenada. Las migajas del flujo de comentarios terminan en la página de entregas; los comentarios se abren como diálogo modal y no como ruta independiente.

#### Scenario: Teacher or admin task navigation
- **WHEN** un docente o administrativo abre el detalle de una materia o las entregas de una tarea
- **THEN** ve la ruta desde «Mis materias» o «Tareas» respectivamente, usando el nombre real de la materia y de la tarea cuando estén disponibles

#### Scenario: Student task navigation
- **WHEN** un estudiante abre el detalle de una tarea
- **THEN** ve «Mis tareas» seguido del nombre real de la tarea, con «Mis tareas» como enlace de regreso

#### Scenario: Comments modal from deliveries
- **WHEN** un docente o administrativo abre los comentarios de una entrega
- **THEN** ve un diálogo modal accesible sobre la página de entregas y las migajas continúan terminando en «Entregas de la tarea»

#### Scenario: Comments modal from student task detail
- **WHEN** un estudiante abre los comentarios desde el detalle de una tarea
- **THEN** ve el mismo diálogo modal accesible sin navegar a una ruta separada, y las migajas permanecen «Mis tareas» > nombre de la tarea
