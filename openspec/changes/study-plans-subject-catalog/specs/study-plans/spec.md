# Spec Delta

## Purpose

Administra el catálogo de planes de estudio de la escuela.

## ADDED Requirements

### Requirement: Study plan catalog
El administrativo SHALL poder crear, editar, listar y desactivar planes de estudio con clave, nombre y estado `active`/`inactive`. La clave MUST ser única dentro de la escuela.

#### Scenario: Create plan
- **WHEN** el administrativo crea un plan con clave "BAS-2026" y nombre "Primaria"
- **THEN** la API responde 201 con el plan activo

#### Scenario: Duplicate key
- **WHEN** el administrativo crea un plan con una clave ya existente en su escuela
- **THEN** la API responde 409 con `CONFLICT` y no se crea

### Requirement: Plan deletion rule
El sistema SHALL rechazar el borrado de un plan que tenga materias asociadas, con 409 `CONFLICT`; el administrativo MUST poder desactivarlo en su lugar. Un plan sin materias SHALL poder borrarse.

#### Scenario: Delete plan with subjects
- **WHEN** el administrativo borra un plan con materias
- **THEN** la API responde 409 y el plan permanece

#### Scenario: Deactivate plan
- **WHEN** el administrativo cambia el estado del plan a `inactive`
- **THEN** el plan deja de ofrecerse al crear materias y las materias existentes se conservan

### Requirement: Study plans screen and access
El menú del administrador SHALL incluir la opción "Planes de estudio" con una tabla de planes y un modal de alta/edición ordenado; las acciones no disponibles (borrar un plan con materias) MUST mostrarse deshabilitadas, no ocultas. Solo el administrativo SHALL acceder a los endpoints, dentro de su escuela, con respuestas sin envoltorio `data` y errores con código HTTP.

#### Scenario: Non-admin access
- **WHEN** un profesor o estudiante llama a `/study-plans`
- **THEN** la API responde 403

#### Scenario: Plan with subjects in the table
- **WHEN** un plan tiene materias
- **THEN** su botón de borrar aparece deshabilitado
