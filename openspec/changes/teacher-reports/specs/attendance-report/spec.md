# Spec Delta

## MODIFIED Requirements

### Requirement: Attendance list PDF
El administrador SHALL poder generar un PDF con la lista de asistencia de un grupo, por asignatura o general, para un periodo definido por fecha de inicio y cantidad de días hábiles. El docente SHALL poder generar el mismo PDF solo para grupos y asignaturas que enseña. El PDF SHALL listar a los estudiantes correspondientes en filas ordenadas por apellido y una columna por fecha, con casillas vacías, y un encabezado con escuela, ciclo, grupo, asignatura y profesor (si aplica) y periodo.

#### Scenario: Subject list for one week
- **WHEN** un administrador pide la lista de la asignatura S del grupo G, inicio lunes 2026-10-05 y 5 días
- **THEN** recibe un PDF con los estudiantes de G que cursan S y columnas del lunes al viernes

#### Scenario: Teacher subject list
- **WHEN** un docente pide la lista de una asignatura que enseña en uno de sus grupos
- **THEN** recibe un PDF con solo los estudiantes vinculados a esa asignatura en ese grupo

#### Scenario: General teacher group list
- **WHEN** un docente pide una lista general de un grupo que enseña
- **THEN** recibe un PDF con estudiantes inscritos en ese grupo y sin encabezado de asignatura

#### Scenario: Weekends skipped
- **WHEN** el periodo de 14 días hábiles empieza en viernes
- **THEN** las columnas omiten sábados y domingos y suman 14 fechas

### Requirement: Report validation and access
El endpoint SHALL permitir solicitudes de administradores y docentes autenticados. SHALL rechazar parámetros inválidos o recursos de otra escuela. Para docentes, SHALL verificar en backend que el grupo y la asignatura solicitados están dentro de las asignaturas que enseñan; el intento de consultar una asignatura o grupo ajeno SHALL responder 403 o 404 sin revelar sus datos. Los selectores de docentes SHALL listar solo sus grupos y asignaturas correspondientes. El administrador SHALL conservar acceso completo dentro de su escuela.

#### Scenario: Invalid days
- **WHEN** `days` es 0 o mayor que 31, o `start_date` no es una fecha válida
- **THEN** la API responde 400

#### Scenario: Other school
- **WHEN** el grupo o la asignatura pertenece a otra escuela
- **THEN** la API responde 404

#### Scenario: Teacher requests another subject
- **WHEN** un docente solicita el reporte para una asignatura que no enseña o un grupo que no tiene asignado
- **THEN** la API responde 403 o 404 y no incluye información del recurso

#### Scenario: Other role
- **WHEN** un estudiante llama al endpoint
- **THEN** la API responde 403

### Requirement: Report output rendering is replaceable
El sistema SHALL construir los datos de cada reporte en una representación de vista independiente del formato de salida. Un renderizador intercambiable SHALL convertir esa representación a una respuesta descargable con contenido, tipo de contenido y nombre de archivo. La lista de asistencia SHALL seguir entregándose como PDF con el comportamiento y la presentación existentes.

#### Scenario: Existing attendance PDF
- **WHEN** un usuario autorizado genera la lista de asistencia
- **THEN** el reporte se representa mediante la implementación PDF y conserva el contenido y presentación existentes

#### Scenario: Alternate renderer consumes report data
- **WHEN** un renderizador alternativo recibe la representación de vista de asistencia
- **THEN** puede consumir título, encabezado y filas sin depender de HTML ni Dompdf
