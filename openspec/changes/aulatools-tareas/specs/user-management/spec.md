# Spec Delta

## Purpose

Permite al administrativo gestionar los profesores y estudiantes de su escuela, incluido el estado de los estudiantes y el cambio de contraseña de cualquier usuario.

## ADDED Requirements

### Requirement: Scope by school
Un administrativo SHALL ver y gestionar únicamente usuarios de su propia escuela.

#### Scenario: Cross-school access
- **WHEN** un administrativo solicita un usuario de otra escuela
- **THEN** el sistema responde 404

### Requirement: Teacher management
El administrativo SHALL poder crear, editar, listar y eliminar profesores (nombre, apellidos, email, contraseña inicial).

#### Scenario: Create teacher
- **WHEN** el administrativo envía datos válidos de un profesor
- **THEN** se crea un usuario con rol profesor en su escuela

### Requirement: Student management
El administrativo SHALL poder crear, editar y listar estudiantes con nombre, apellidos, matrícula, fecha de nacimiento, email, contraseña inicial y estado activo/inactivo. La matrícula MUST ser única dentro de la escuela.

#### Scenario: Duplicate enrollment number
- **WHEN** se crea un estudiante con una matrícula ya existente en la misma escuela
- **THEN** el sistema rechaza la operación

#### Scenario: Deactivate student
- **WHEN** el administrativo marca un estudiante como inactivo
- **THEN** el estudiante deja de poder iniciar sesión y se conservan sus datos académicos

### Requirement: Admin password reset
El administrativo SHALL poder establecer una nueva contraseña para cualquier usuario de su escuela. Profesores y estudiantes MUST NOT poder cambiar la contraseña de otros.

#### Scenario: Admin resets password
- **WHEN** el administrativo envía una nueva contraseña para un usuario de su escuela
- **THEN** el usuario puede iniciar sesión con la nueva contraseña

#### Scenario: Non-admin attempts reset
- **WHEN** un profesor o estudiante intenta cambiar la contraseña de otro usuario
- **THEN** el sistema responde 403
