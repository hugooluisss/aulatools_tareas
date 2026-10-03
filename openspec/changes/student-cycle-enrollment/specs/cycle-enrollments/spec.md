# Spec Delta

## Purpose

Inscribe a los estudiantes por ciclo y grupo, permite cambiar o remover la inscripción y define cuándo un estudiante está inscrito en el ciclo activo.

## ADDED Requirements

### Requirement: Cycle enrollment record
El administrativo SHALL poder inscribir a un estudiante de su escuela en un ciclo y un grupo. La inscripción MUST registrar estudiante, ciclo, grupo y fecha. El grupo MUST pertenecer al ciclo indicado y el ciclo MUST estar activo. Un estudiante MUST tener como máximo una inscripción por ciclo.

#### Scenario: Enroll student in cycle and group
- **WHEN** el administrativo inscribe a un estudiante en el ciclo activo y en un grupo de ese ciclo
- **THEN** se crea la inscripción con la fecha actual y la API responde 201

#### Scenario: Group from another cycle
- **WHEN** el administrativo elige un grupo que pertenece a otro ciclo
- **THEN** la API responde 400 con `VALIDATION_ERROR` y no se crea la inscripción

#### Scenario: Duplicate in the same cycle
- **WHEN** el estudiante ya tiene una inscripción en ese ciclo
- **THEN** la API responde 409 con `CONFLICT` y no se crea otra

#### Scenario: Finished cycle
- **WHEN** el administrativo intenta inscribir en un ciclo finalizado
- **THEN** la API responde 422 con `INVALID_STATE`

### Requirement: Enrollment creates subject enrollments
Al crear una inscripción, el sistema SHALL inscribir al estudiante en todas las materias del grupo registrando el grupo como origen, sin duplicar materias en las que ya estuviera inscrito, y generando sus filas de entrega para las tareas existentes.

#### Scenario: Group with seven subjects
- **WHEN** el estudiante se inscribe en un grupo con 7 materias
- **THEN** queda inscrito en esas 7 materias

### Requirement: Change group
El administrativo SHALL poder cambiar el grupo de una inscripción dentro del mismo ciclo. El sistema MUST quitar las inscripciones a materias originadas por el grupo anterior e inscribir al estudiante en las materias del nuevo grupo, conservando las inscripciones individuales.

#### Scenario: Move to another group
- **WHEN** el administrativo cambia la inscripción del grupo "1A" al grupo "1B" del mismo ciclo
- **THEN** el estudiante deja las materias de "1A" e ingresa a las de "1B"

### Requirement: Remove enrollment
El administrativo SHALL poder remover una inscripción. El sistema MUST quitar las inscripciones a materias cuyo origen es el grupo de esa inscripción y conservar las materias inscritas individualmente. El registro del estudiante MUST permanecer.

#### Scenario: Drop enrollment
- **WHEN** el administrativo remueve la inscripción de un estudiante
- **THEN** la API responde 204, el estudiante conserva su registro y sale de las materias del grupo

#### Scenario: Individual subject kept
- **WHEN** el estudiante tenía además una materia inscrita individualmente y se remueve su inscripción
- **THEN** conserva esa materia

### Requirement: Student status in the active cycle
Un registro de estudiante con estado activo SHALL considerarse estudiante. El listado de estudiantes MUST indicar, para cada uno, su inscripción vigente (ciclo y grupo) cuando exista en un ciclo activo, o ninguna en caso contrario. El listado MUST obtenerse sin consultas por fila.

#### Scenario: Enrolled student
- **WHEN** el administrativo lista estudiantes y uno tiene inscripción en el ciclo activo
- **THEN** su fila incluye ciclo y grupo de esa inscripción

#### Scenario: Student without enrollment
- **WHEN** un estudiante activo no tiene inscripción en ningún ciclo activo
- **THEN** su fila indica que no tiene inscripción

### Requirement: Student not already enrolled in destination cycle
Antes de crear una inscripción o reinscripción, el sistema SHALL verificar que el estudiante no tenga ya una inscripción en el ciclo destino. Esta verificación MUST aplicar a cada estudiante de una operación masiva.

#### Scenario: Student already in destination cycle
- **WHEN** el administrativo reinscribe a un estudiante que ya tiene inscripción en el ciclo destino
- **THEN** la API responde 409 con `CONFLICT`, el mensaje incluye el id de ese estudiante y no se crea ninguna inscripción

### Requirement: Aspirants
Un aspirante SHALL ser un estudiante con estado activo que nunca ha tenido una inscripción en ningún ciclo, pasado o actual. El administrativo SHALL poder listar los aspirantes de su escuela.

#### Scenario: List aspirants
- **WHEN** el administrativo consulta los aspirantes
- **THEN** recibe solo estudiantes activos sin ninguna inscripción histórica

#### Scenario: Previously enrolled student
- **WHEN** un estudiante tuvo una inscripción en un ciclo ya finalizado
- **THEN** no aparece como aspirante

### Requirement: Re-enrollable students
Un estudiante SHALL considerarse reinscribible cuando está activo, tiene al menos una inscripción previa y todas las materias en las que está inscrito por su última inscripción tienen estado `finished`. El administrativo SHALL poder listar los estudiantes reinscribibles de su escuela.

#### Scenario: All subjects finished
- **WHEN** un estudiante tiene todas las materias de su última inscripción finalizadas
- **THEN** aparece en la lista de reinscribibles

#### Scenario: Pending subject
- **WHEN** a un estudiante le queda una materia en estado `in_progress`
- **THEN** no aparece como reinscribible

### Requirement: Bulk enrollment of aspirants
El administrativo SHALL poder inscribir a varios aspirantes a la vez en un ciclo activo y un grupo de ese ciclo, indicando `type` igual a `enrollment`. La operación MUST ser atómica y MUST rechazar a quien no sea aspirante.

#### Scenario: Enroll several aspirants
- **WHEN** el administrativo inscribe a 3 aspirantes en el ciclo activo y un grupo de ese ciclo
- **THEN** la API responde 201 con las 3 inscripciones y cada estudiante queda en las materias del grupo

#### Scenario: Non-aspirant in the selection
- **WHEN** entre los seleccionados hay un estudiante que ya tuvo inscripción
- **THEN** la API responde 422 con `INVALID_STATE`, el mensaje incluye su id y no se crea ninguna inscripción

### Requirement: Bulk re-enrollment
El administrativo SHALL poder reinscribir a varios estudiantes reinscribibles a la vez, indicando `type` igual a `reenrollment`, en un ciclo activo distinto al de su inscripción anterior y un grupo de ese ciclo. La operación MUST ser atómica, MUST rechazar a quien no sea reinscribible y MUST conservar la inscripción anterior como historial.

#### Scenario: Re-enroll to a new cycle
- **WHEN** el administrativo reinscribe a 2 estudiantes con materias finalizadas en un nuevo ciclo activo
- **THEN** la API responde 201, se crea una inscripción nueva por estudiante y las anteriores permanecen

#### Scenario: Not re-enrollable
- **WHEN** entre los seleccionados hay un estudiante con una materia pendiente
- **THEN** la API responde 422 con `INVALID_STATE`, el mensaje incluye su id y no se crea ninguna inscripción

#### Scenario: Same cycle as previous
- **WHEN** el ciclo destino es el de la inscripción anterior del estudiante
- **THEN** la API responde 409 con `CONFLICT` y no se crea ninguna inscripción

### Requirement: Enrollment screens
El menú del administrador SHALL incluir las opciones Inscripciones y Reinscripciones. Cada pantalla SHALL listar sus candidatos con selección múltiple, pedir un ciclo activo y un grupo de ese ciclo, y ejecutar la operación masiva correspondiente. La pantalla de Reinscripciones MUST excluir de los ciclos destino el ciclo de la inscripción anterior de los seleccionados. Las pantallas de Estudiantes y la lista de Grupos MUST NOT ofrecer acciones de inscripción de estudiantes.

#### Scenario: Enroll from the screen
- **WHEN** el administrativo selecciona aspirantes, elige ciclo y grupo y pulsa "Inscribir seleccionados"
- **THEN** los aspirantes dejan la lista y aparecen inscritos en la tabla de Estudiantes

#### Scenario: Students screen
- **WHEN** el administrativo edita un estudiante
- **THEN** el modal no muestra controles de inscripción

#### Scenario: Groups list
- **WHEN** el administrativo abre la lista de Grupos
- **THEN** las filas no muestran selector de estudiante ni botón de inscribir

### Requirement: Add students to a subject
El administrativo SHALL poder agregar varios estudiantes a una materia desde la lista de Materias mediante un modal. El modal MUST listar solo estudiantes con inscripción en el ciclo de la materia, que MUST estar activo, y MUST excluir a quienes ya están inscritos en esa materia. El registro MUST ser atómico, individual (sin grupo de origen) y MUST generar las filas de entrega de las tareas existentes de la materia.

#### Scenario: Add selected students
- **WHEN** el administrativo abre "Agregar estudiante" en una materia, selecciona 2 estudiantes inscritos en su ciclo y confirma
- **THEN** la API responde 201 y ambos quedan inscritos en la materia con sus entregas generadas

#### Scenario: Student without inscription in the subject's cycle
- **WHEN** se intenta agregar a un estudiante sin inscripción en el ciclo de la materia
- **THEN** la API responde 400 con `VALIDATION_ERROR`, el mensaje incluye su id y no se agrega a nadie

#### Scenario: Already in the subject
- **WHEN** algún estudiante seleccionado ya está inscrito en la materia
- **THEN** la API responde 409 con `CONFLICT`, el mensaje incluye su id y no se agrega a nadie

#### Scenario: Finished cycle
- **WHEN** el ciclo de la materia está finalizado
- **THEN** el ícono "Agregar estudiante" está deshabilitado y la API responde 422 con `INVALID_STATE`

### Requirement: Subjects with students cannot be deleted
El sistema SHALL rechazar el borrado de una materia que tenga estudiantes inscritos, con 409 `CONFLICT`. La lista de materias MUST indicar cuántos estudiantes tiene cada una sin consultas por fila, y la interfaz MUST mostrar el botón de borrar deshabilitado (no oculto) cuando la materia tenga estudiantes.

#### Scenario: Delete subject with students
- **WHEN** el administrativo intenta borrar una materia con estudiantes inscritos
- **THEN** la API responde 409 y la materia permanece

#### Scenario: Disabled delete button
- **WHEN** una materia tiene estudiantes
- **THEN** su botón de borrar aparece deshabilitado

### Requirement: Disabled instead of hidden actions
Las acciones de fila que no estén disponibles (por ejemplo "Agregar estudiante" con el ciclo de la materia finalizado) SHALL mostrarse deshabilitadas en lugar de ocultarse.

#### Scenario: Finished cycle
- **WHEN** el ciclo de la materia está finalizado
- **THEN** el ícono "Agregar estudiante" aparece deshabilitado

### Requirement: Matrícula equals student id
La matrícula de un estudiante SHALL ser siempre su id. El sistema MUST NOT pedir ni aceptar una matrícula capturada en el alta o edición; las vistas que muestran la matrícula MUST mostrar el id.

#### Scenario: Create student
- **WHEN** el administrativo da de alta a un estudiante sin matrícula
- **THEN** el estudiante queda registrado y su matrícula es su id

#### Scenario: Existing students
- **WHEN** se aplica la migración
- **THEN** la matrícula de los estudiantes existentes pasa a ser su id y siguen activos y sin inscripción

### Requirement: Enrollment access control and errors
Solo el administrativo SHALL poder consultar, crear, cambiar y remover inscripciones, y solo dentro de su escuela. Las respuestas MUST devolver el recurso sin envoltorio `data` y los errores MUST usar códigos HTTP con `{"error":{"code","message"}}`.

#### Scenario: Non-admin access
- **WHEN** un profesor o estudiante llama a un endpoint de inscripciones
- **THEN** la API responde 403

#### Scenario: Student from another school
- **WHEN** el administrativo usa un estudiante de otra escuela
- **THEN** la API responde 404
