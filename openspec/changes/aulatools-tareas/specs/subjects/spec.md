# Spec Delta

## Purpose

Gestiona las materias de la escuela, su profesor, su ciclo y su estado, y lo que cada profesor puede ver de ellas.

## ADDED Requirements

### Requirement: Subject management
El administrativo SHALL poder crear, editar y listar materias con nombre, ciclo, profesor asignado y estado En curso o Terminada. Toda materia MUST tener exactamente un profesor.

#### Scenario: Create subject
- **WHEN** el administrativo crea una materia con ciclo activo y un profesor de su escuela
- **THEN** la materia se crea En curso

#### Scenario: Teacher of other school
- **WHEN** se asigna un profesor de otra escuela
- **THEN** el sistema rechaza la operación

### Requirement: Teacher sees only own subjects
El profesor SHALL ver únicamente las materias que tiene asignadas y la lista de estudiantes inscritos en ellas.

#### Scenario: Teacher lists subjects
- **WHEN** un profesor consulta sus materias
- **THEN** recibe solo las materias donde es el profesor asignado

#### Scenario: Teacher accesses foreign subject
- **WHEN** un profesor consulta el detalle o los estudiantes de una materia que no es suya
- **THEN** el sistema responde 404

### Requirement: Student sees own subjects
El estudiante SHALL ver únicamente las materias en las que está inscrito.

#### Scenario: Student lists subjects
- **WHEN** un estudiante consulta sus materias
- **THEN** recibe solo aquellas en que tiene inscripción
