# Spec Delta

## Purpose

Notas administrativas por estudiante, no visibles para el estudiante.

## ADDED Requirements

### Requirement: Student notes
El administrador y los profesores del estudiante SHALL poder ver y agregar notas sobre un estudiante. Un profesor lo es si tiene al menos una materia de ese estudiante como profesor del vínculo o de la materia. El estudiante SHALL NOT poder verlas ni agregarlas.

#### Scenario: Admin adds a note
- **WHEN** el administrador agrega una nota a un estudiante
- **THEN** la nota aparece en el historial con autor, rol y fecha, la más reciente primero

#### Scenario: Teacher of the student
- **WHEN** un profesor que imparte una materia al estudiante consulta sus notas
- **THEN** recibe el historial y puede agregar notas

#### Scenario: Teacher without relation
- **WHEN** un profesor sin materias del estudiante consulta sus notas
- **THEN** la API responde 403

#### Scenario: Student access
- **WHEN** un estudiante llama al endpoint de notas
- **THEN** la API responde 404

#### Scenario: Empty note
- **WHEN** la nota está vacía o supera 2000 caracteres
- **THEN** la API responde 400
