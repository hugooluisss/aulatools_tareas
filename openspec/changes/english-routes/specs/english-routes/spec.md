# Spec Delta

## Purpose

Todas las rutas del sistema están en inglés.

## ADDED Requirements

### Requirement: Backend routes in English
Todas las rutas HTTP del backend SHALL usar segmentos en inglés; las inscripciones SHALL exponerse bajo `/enrollments`.

#### Scenario: List enrollments
- **WHEN** un administrador llama `GET /enrollments`
- **THEN** recibe las inscripciones y `GET /inscriptions` responde 404

### Requirement: Frontend routes in English
Todas las rutas del router del frontend SHALL estar en inglés según el mapa de design.md, manteniendo los textos de pantalla en español y las restricciones por rol.

#### Scenario: Navigate from the menu
- **WHEN** un administrador abre Inscripciones desde el menú
- **THEN** el navegador muestra `/enrollments` y la pantalla de inscripciones

#### Scenario: Default route
- **WHEN** se abre `/` o una ruta desconocida
- **THEN** se redirige a `/home`
