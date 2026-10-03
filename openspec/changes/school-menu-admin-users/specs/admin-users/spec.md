# Spec Delta

## Purpose

Administración de los administradores de la escuela.

## ADDED Requirements

### Requirement: Manage administrators
El administrador SHALL poder listar (paginado), crear, editar y eliminar a los administradores de su escuela y restablecer su contraseña.

#### Scenario: Create administrator
- **WHEN** el administrador crea un usuario con nombre, apellido, correo y contraseña válidos
- **THEN** el nuevo administrador aparece en la lista y puede iniciar sesión

### Requirement: Administrator safeguards
Un administrador SHALL NOT poder eliminarse a sí mismo y la escuela SHALL conservar al menos un administrador.

#### Scenario: Delete self
- **WHEN** un administrador intenta eliminar su propio usuario
- **THEN** la API rechaza la operación con un error y no lo elimina

#### Scenario: Last administrator
- **WHEN** se intenta eliminar al último administrador de la escuela
- **THEN** la API rechaza la operación con un error
