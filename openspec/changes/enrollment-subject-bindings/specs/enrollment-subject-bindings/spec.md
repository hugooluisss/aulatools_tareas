# Spec Delta

## Purpose

Define la inscripción del estudiante en un ciclo y grupo (`enrollments`) y los vínculos con materias (`enrollment_subject_bindings`) con integridad referencial.

## ADDED Requirements

### Requirement: Enrollment and subject bindings model
Una inscripción SHALL registrar estudiante, ciclo, grupo y fecha, con unicidad por (estudiante, ciclo). Cada materia de una inscripción SHALL guardarse como un vínculo (`enrollment_id`, `subject_id`) con unicidad por (inscripción, materia), sin columnas redundantes de estudiante o ciclo.

#### Scenario: Enroll in a group
- **WHEN** el administrativo inscribe a un estudiante en el Ciclo 14 y el Grupo 1A de 7 materias
- **THEN** se crea una inscripción y 7 vínculos asociados a ella

#### Scenario: Derived student and cycle
- **WHEN** se consulta el vínculo de una materia
- **THEN** el estudiante y el ciclo se obtienen de su inscripción

### Requirement: Enrollment creates subject bindings and deliveries
Al crear una inscripción (individual, masiva o reinscripción), el sistema SHALL crear los vínculos de todas las materias del grupo con una sola operación de inserción por lote y SHALL generar las filas de entrega de las tareas existentes de esas materias en el ciclo de la inscripción, sin duplicar vínculos ya existentes.

#### Scenario: Existing tasks
- **WHEN** una materia del grupo ya tiene tareas en el ciclo de la inscripción
- **THEN** el estudiante recibe sus entregas pendientes

### Requirement: Removing an enrollment removes its bindings
Al remover una inscripción, el sistema SHALL borrar todos sus vínculos con materias, incluidos los agregados de forma individual, y SHALL conservar el registro del estudiante.

#### Scenario: Drop enrollment
- **WHEN** el administrativo remueve la inscripción de un estudiante que tenía además la materia Artes agregada individualmente
- **THEN** la API responde 204 y el estudiante deja ambas, las 7 del grupo y Artes

### Requirement: Change group of an enrollment
Al cambiar el grupo de una inscripción dentro del mismo ciclo, el sistema SHALL quitar los vínculos de las materias del grupo anterior, SHALL agregar los de las materias del grupo nuevo y MUST conservar los vínculos de materias que no pertenecían al grupo anterior. Una materia agregada individualmente que también pertenecía al grupo anterior se considera del grupo y se reemplaza.

#### Scenario: Move to another group
- **WHEN** la inscripción pasa del grupo "1A" al grupo "1B" del mismo ciclo
- **THEN** el estudiante deja las materias de "1A", ingresa a las de "1B" y conserva Artes

### Requirement: Group subject changes
Al agregar materias a un grupo, el sistema SHALL agregar los vínculos correspondientes a los estudiantes inscritos en ese grupo, por lote. Al quitar una materia de un grupo, el sistema MUST NOT quitar vínculos de los estudiantes.

#### Scenario: Add subject to group
- **WHEN** el administrativo agrega una materia a un grupo con 5 estudiantes inscritos
- **THEN** los 5 reciben el vínculo con esa materia

#### Scenario: Remove subject from group
- **WHEN** el administrativo quita una materia de un grupo con estudiantes inscritos
- **THEN** los estudiantes conservan su vínculo con esa materia

### Requirement: Individual subject binding
El administrativo SHALL poder agregar estudiantes a una materia con `POST /subjects/{id}/students/bulk` indicando `cycle_id` y `student_ids`; el sistema SHALL crear el vínculo en la inscripción del estudiante en ese ciclo. Si el ciclo no está activo la API MUST responder 422, si un estudiante no tiene inscripción en ese ciclo 400, si ya está vinculado 409 y si no es de la escuela 404, con los ids en el mensaje y de forma atómica. `DELETE /subjects/{id}/students/{student_id}?cycle_id=` SHALL quitar el vínculo.

#### Scenario: Add student to extracurricular subject
- **WHEN** el administrativo agrega al estudiante 57, inscrito en el ciclo 14, a la materia Artes
- **THEN** la API responde 201 y existe un vínculo entre su inscripción del ciclo 14 y Artes

#### Scenario: Student without enrollment in the cycle
- **WHEN** se agrega un estudiante sin inscripción en el ciclo indicado
- **THEN** la API responde 400 con su id y no se agrega a nadie

### Requirement: Queries use bindings
Los estudiantes de una materia en un ciclo, `students_count` (estudiantes distintos), las entregas de tareas, las tareas visibles para estudiantes, el calendario por materia y los criterios de aspirante y reinscribible SHALL obtenerse de `enrollments` y `enrollment_subject_bindings`, sin consultas por fila.

#### Scenario: Students of a subject
- **WHEN** el administrativo consulta los estudiantes de una materia en el ciclo 14
- **THEN** recibe los estudiantes con un vínculo a esa materia en una inscripción del ciclo 14

### Requirement: Legacy group enrollment route removed
El sistema SHALL NOT ofrecer `POST /groups/{id}/students`; la inscripción a grupos MUST ocurrir únicamente al crear una inscripción.

#### Scenario: Removed route
- **WHEN** un cliente llama a `POST /groups/{id}/students`
- **THEN** la API responde 404

### Requirement: Data preserved by the migration
La migración SHALL conservar las inscripciones existentes y convertir cada fila antigua de inscripción a materia en un vínculo de la inscripción del estudiante en ese ciclo, descartando las filas sin inscripción correspondiente.

#### Scenario: Existing data
- **WHEN** se aplica la migración con las 5 inscripciones y 35 inscripciones a materia existentes
- **THEN** quedan 5 inscripciones y 35 vínculos, 7 por estudiante
