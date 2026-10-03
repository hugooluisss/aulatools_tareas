# Spec Delta

## Purpose

Muestra fechas importantes de la escuela, administradas por el administrativo, con opción de agregarlas a Google Calendar.

## ADDED Requirements

### Requirement: Event management by admin
Solo el administrativo SHALL poder crear, editar y eliminar eventos (título, descripción, fecha/hora inicio, fecha/hora fin) con ámbito de escuela completa o de una materia.

#### Scenario: Create school event
- **WHEN** el administrativo crea un evento de ámbito escuela
- **THEN** todos los usuarios de la escuela lo ven

#### Scenario: Non-admin creates event
- **WHEN** un profesor o estudiante intenta crear un evento
- **THEN** el sistema responde 403

### Requirement: Calendar view
Cada usuario SHALL ver un calendario mensual con los eventos de su escuela, los de sus materias y las fechas de vencimiento de sus tareas.

#### Scenario: Task due date shown
- Task due dates use `YYYY-MM-DD` and appear on that calendar day.
- **WHEN** el estudiante abre el calendario
- **THEN** ve el vencimiento de sus tareas junto con los eventos

#### Scenario: Subject event visibility
- **WHEN** un evento tiene ámbito de materia
- **THEN** solo lo ven los estudiantes inscritos, el profesor de la materia y el administrativo

### Requirement: Add to Google Calendar
Cada evento SHALL ofrecer un botón que abra el enlace de plantilla de Google Calendar con título, descripción y fechas precargados, sin requerir autenticación con Google.

#### Scenario: Click add
- **WHEN** el usuario pulsa "Agregar a Google Calendar"
- **THEN** se abre una nueva pestaña de Google Calendar con el evento precargado
