# Spec Delta

## Purpose

Difunde avisos generales a la comunidad estudiantil durante un periodo definido.

## ADDED Requirements

### Requirement: Announcement management
El administrativo SHALL poder crear, editar y eliminar avisos con título, contenido, fecha de inicio y fecha de fin, dirigidos a toda su escuela.

#### Scenario: Create announcement
- **WHEN** el administrativo crea un aviso con periodo válido
- **THEN** el aviso queda registrado para su escuela

#### Scenario: Invalid period
- **WHEN** la fecha de fin es anterior a la de inicio
- **THEN** el sistema rechaza la operación

### Requirement: Visibility period
Los estudiantes y profesores SHALL ver únicamente los avisos de su escuela cuyo periodo incluya la fecha actual.

#### Scenario: Active announcement
- **WHEN** hoy está dentro del periodo del aviso
- **THEN** aparece en la lista

#### Scenario: Expired or future announcement
- **WHEN** hoy está fuera del periodo
- **THEN** no aparece para estudiantes ni profesores, pero el administrativo sigue viéndolo en su gestión
