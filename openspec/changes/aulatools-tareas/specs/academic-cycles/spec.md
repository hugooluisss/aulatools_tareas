# Spec Delta

## Purpose

Mantiene el catálogo de ciclos escolares de cada escuela y propaga su cierre a las materias.

## ADDED Requirements

### Requirement: Cycle catalog
El administrativo SHALL poder crear, editar y listar ciclos escolares (nombre, fecha inicio, fecha fin) con estado Activo o Finalizado. Los ciclos pertenecen a una escuela.

#### Scenario: Create cycle
- **WHEN** el administrativo crea un ciclo con nombre y fechas válidas
- **THEN** el ciclo queda en estado Activo

#### Scenario: Invalid dates
- **WHEN** la fecha fin es anterior a la fecha inicio
- **THEN** el sistema rechaza la operación

### Requirement: Finalizing a cycle ends its subjects
Al cambiar un ciclo a Finalizado, el sistema SHALL cambiar a Terminada todas las materias del ciclo, en una sola transacción.

#### Scenario: Finalize cycle
- **WHEN** el administrativo finaliza un ciclo con materias En curso
- **THEN** todas esas materias quedan Terminada

#### Scenario: Finalized cycle is closed
- **WHEN** se intenta crear una materia en un ciclo Finalizado
- **THEN** el sistema rechaza la operación
