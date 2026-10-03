# Spec Delta

## Purpose

Lista de asistencia imprimible en PDF por periodo.

## ADDED Requirements

### Requirement: Attendance list PDF
El administrador SHALL poder generar un PDF con la lista de asistencia de un grupo, por asignatura o general, para un periodo definido por fecha de inicio y cantidad de días hábiles. El PDF SHALL listar a los estudiantes en filas ordenadas por apellido y una columna por fecha, con casillas vacías, y un encabezado con escuela, ciclo, grupo, asignatura y profesor (si aplica) y periodo.

#### Scenario: Subject list for one week
- **WHEN** el administrador pide la lista de la asignatura S del grupo G, inicio lunes 2026-10-05 y 5 días
- **THEN** recibe un PDF con los estudiantes de G que cursan S y columnas del lunes al viernes

#### Scenario: General group list
- **WHEN** no se envía `subject_id`
- **THEN** el PDF incluye a todos los estudiantes inscritos en el grupo y el encabezado no menciona asignatura

#### Scenario: Weekends skipped
- **WHEN** el periodo de 14 días hábiles empieza en viernes
- **THEN** las columnas omiten sábados y domingos y suman 14 fechas

### Requirement: Report validation and access
El endpoint SHALL ser exclusivo del administrador y SHALL rechazar parámetros inválidos o recursos de otra escuela.

#### Scenario: Invalid days
- **WHEN** `days` es 0 o mayor que 31, o `start_date` no es una fecha válida
- **THEN** la API responde 400

#### Scenario: Other school
- **WHEN** el grupo o la asignatura pertenece a otra escuela
- **THEN** la API responde 404

#### Scenario: Not an admin
- **WHEN** un profesor o estudiante llama al endpoint
- **THEN** la API responde 403
