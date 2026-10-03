# Spec Delta

## Purpose

Hace que las inscripciones a materia y las tareas pertenezcan a un ciclo, para que una misma materia se curse en varios ciclos sin mezclar datos.

## ADDED Requirements

### Requirement: Subject enrollments carry a cycle
Cada inscripción de un estudiante a una materia SHALL registrar el ciclo, con unicidad por (estudiante, materia, ciclo). Las inscripciones originadas por un grupo MUST usar el ciclo del grupo.

#### Scenario: Group enrollment cycle
- **WHEN** un estudiante se inscribe en un grupo del ciclo 14
- **THEN** sus inscripciones a las materias del grupo quedan con ciclo 14

#### Scenario: Same subject in two cycles
- **WHEN** un estudiante se reinscribe en otro ciclo con una materia que ya cursó
- **THEN** se crea una inscripción nueva para el ciclo nuevo y la anterior se conserva

### Requirement: Individual enrollment with cycle
El alta individual de estudiantes a una materia (`POST /subjects/{id}/students/bulk`) SHALL recibir `cycle_id`: el ciclo MUST estar activo y cada estudiante MUST tener una inscripción en ese ciclo. El modal "Agregar estudiante" MUST preseleccionar el ciclo cuando solo haya un ciclo activo y pedirlo cuando haya varios; los candidatos son los estudiantes inscritos en el ciclo elegido que no estén ya inscritos en esa materia y ciclo.

#### Scenario: Two active cycles
- **WHEN** existen dos ciclos activos
- **THEN** el modal pide elegir el ciclo antes de listar candidatos

#### Scenario: Student without inscription in the cycle
- **WHEN** se agrega un estudiante sin inscripción en el ciclo indicado
- **THEN** la API responde 400 con `VALIDATION_ERROR` e incluye su id

### Requirement: Tasks belong to a subject and cycle
Cada tarea SHALL pertenecer a una materia y a un ciclo activo indicado al crearla. Las entregas MUST generarse solo para los estudiantes inscritos en esa materia y ese ciclo, y las consultas de tareas, entregas y comentarios MUST filtrarse por ciclo.

#### Scenario: Create task for a cycle
- **WHEN** el profesor crea una tarea de su materia para el ciclo 14
- **THEN** se generan entregas solo para los estudiantes inscritos en esa materia en el ciclo 14

#### Scenario: Student sees only their cycles
- **WHEN** un estudiante consulta sus tareas
- **THEN** ve las tareas de los ciclos en los que está inscrito en la materia

### Requirement: Cycle filter in screens
Las pantallas de profesor (materias y tareas), estudiante (materias y tareas) y "Ver estudiantes" de una materia SHALL filtrar por ciclo, usando por defecto el ciclo activo; cuando haya varios ciclos activos MUST ofrecer un selector.

#### Scenario: Students of a subject by cycle
- **WHEN** el administrativo abre "Ver estudiantes" de una materia
- **THEN** ve los estudiantes inscritos en el ciclo seleccionado

### Requirement: Re-enrollable by finished cycle
Un estudiante SHALL considerarse reinscribible cuando está activo, su última inscripción pertenece a un ciclo con estado `finished` y no tiene inscripción en un ciclo activo. Este requisito reemplaza el criterio "todas sus materias finalizadas" del cambio `student-cycle-enrollment`; las demás reglas de reinscripción (ciclo destino activo y distinto del anterior, alta atómica, historial conservado) no cambian.

#### Scenario: Finished cycle
- **WHEN** la última inscripción de un estudiante es de un ciclo finalizado y no tiene inscripción activa
- **THEN** aparece en la lista de reinscribibles

#### Scenario: Active inscription
- **WHEN** el estudiante ya tiene una inscripción en un ciclo activo
- **THEN** no aparece como reinscribible

### Requirement: Calendar and announcements by cycle
Los eventos de calendario ligados a una materia SHALL mostrarse a los estudiantes inscritos en esa materia en un ciclo activo y al profesor de la materia; los avisos no cambian.

#### Scenario: Event of a subject
- **WHEN** un estudiante inscrito en una materia en un ciclo activo abre el calendario
- **THEN** ve los eventos de esa materia
