# Spec Delta

## Purpose

Define el profesor por defecto del grupo y el profesor de cada vínculo materia-inscripción.

## ADDED Requirements

### Requirement: Group default teacher
Un grupo SHALL poder tener un profesor por defecto (`teacher_id`), que debe ser un profesor de la misma escuela. El campo SHALL ser opcional y se devuelve en las respuestas del grupo.

#### Scenario: Assign teacher to group
- **WHEN** el administrativo crea o edita un grupo con un `teacher_id` válido
- **THEN** el grupo guarda ese profesor

#### Scenario: Group without default teacher
- **WHEN** el administrativo crea o edita un grupo con `teacher_id` null
- **THEN** el grupo queda sin profesor por defecto y devuelve `teacher_id` null

#### Scenario: Teacher of another school
- **WHEN** se envía el `teacher_id` de un profesor de otra escuela
- **THEN** la API responde 404 y no cambia el grupo

### Requirement: Bindings inherit the group teacher
Al crear un vínculo materia-inscripción por cualquier vía, el sistema SHALL guardar en el vínculo el profesor del grupo de la inscripción o, si el grupo no tiene, el de la materia. Cambiar después el profesor del grupo SHALL NOT modificar vínculos existentes.

#### Scenario: Enroll in a group with teacher
- **WHEN** se inscribe a un estudiante en un grupo con profesor P y 7 materias
- **THEN** los 7 vínculos quedan con `teacher_id` = P

#### Scenario: Group without teacher
- **WHEN** el grupo no tiene profesor
- **THEN** cada vínculo toma el profesor de su materia

#### Scenario: Group teacher changes later
- **WHEN** se cambia el profesor del grupo
- **THEN** los vínculos existentes conservan su profesor

### Requirement: Bulk teacher reassignment
El administrador SHALL poder cambiar el profesor de los vínculos de una materia y ciclo para una lista de estudiantes mediante `PUT /subjects/{id}/students/teacher`, en una sola operación atómica.

#### Scenario: Reassign a set
- **WHEN** el admin envía `cycle_id`, `student_ids` [1,2,3] y `teacher_id` Q
- **THEN** los 3 vínculos de esa materia y ciclo quedan con Q y los demás no cambian

#### Scenario: Student without binding
- **WHEN** alguno de los estudiantes no tiene vínculo con la materia en el ciclo
- **THEN** la API responde 400 y no cambia ninguno

#### Scenario: Not an admin
- **WHEN** un profesor o estudiante llama al endpoint
- **THEN** la API responde 403
