# Spec Delta

## Purpose

Organiza materias en grupos e inscribe estudiantes a un grupo completo o a materias individuales.

## ADDED Requirements

### Requirement: Group management
El administrativo SHALL poder crear, editar y listar grupos (por ejemplo "3A") y asociarles N materias de su escuela.

#### Scenario: Group with subjects
- **WHEN** el administrativo crea el grupo "3A" y le asocia 7 materias
- **THEN** el grupo queda con esas 7 materias

### Requirement: Enroll student in a group
Al inscribir un estudiante a un grupo, el sistema SHALL inscribirlo en todas las materias del grupo, registrando que el origen es el grupo.

#### Scenario: Enroll in group
- **WHEN** el administrativo inscribe a un estudiante en un grupo de 7 materias
- **THEN** el estudiante queda inscrito en las 7 materias

#### Scenario: Existing individual enrollment
- **WHEN** el estudiante ya estaba inscrito individualmente en una de las materias del grupo
- **THEN** no se duplica la inscripción

### Requirement: Enroll student in a single subject
El administrativo SHALL poder inscribir un estudiante en una materia individual (por ejemplo extraescolar) y quitarlo de una materia, incluso si proviene de un grupo.

#### Scenario: Individual enrollment
- **WHEN** el administrativo inscribe a un estudiante en una materia
- **THEN** el estudiante aparece en la lista de esa materia

#### Scenario: Remove from one subject
- **WHEN** el administrativo quita al estudiante de una materia de su grupo
- **THEN** conserva las demás inscripciones

### Requirement: Enrollment creates task rows
Cuando un estudiante se inscribe en una materia con tareas existentes, el sistema SHALL generarle su fila de entrega para esas tareas no canceladas.

#### Scenario: Late enrollment
- **WHEN** un estudiante se inscribe en una materia que ya tiene 3 tareas
- **THEN** el estudiante ve esas 3 tareas en Pendiente
