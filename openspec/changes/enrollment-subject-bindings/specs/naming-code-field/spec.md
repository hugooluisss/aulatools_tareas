# Spec Delta

## Purpose

Unifica el nombre del identificador único de materias y planes de estudio en inglés.

## ADDED Requirements

### Requirement: Code field
Las materias y los planes de estudio SHALL identificarse con el campo `code`, único dentro de la escuela, en la base de datos, los cuerpos y respuestas de la API, el catálogo de Bruno y los tipos del frontend. La etiqueta visible MUST seguir siendo "Clave".

#### Scenario: Create subject
- **WHEN** el administrativo crea una materia con `code` "MAT-101"
- **THEN** la API responde 201 con `code` "MAT-101"

#### Scenario: Duplicate code
- **WHEN** el administrativo usa un `code` ya existente en su escuela
- **THEN** la API responde 409 con `CONFLICT`

#### Scenario: Screen label
- **WHEN** el administrativo abre Materias o Planes de estudio
- **THEN** la columna y el campo se muestran como "Clave"
