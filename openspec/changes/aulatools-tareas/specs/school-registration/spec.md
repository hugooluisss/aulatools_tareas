# Spec Delta

## Purpose

Permite que una escuela se dé de alta por sí misma, creando en un solo paso la escuela y su primer usuario administrativo.

## ADDED Requirements

### Requirement: Public school registration
El sistema SHALL ofrecer un formulario público que cree una escuela (nombre) y su primer usuario administrativo (nombre, apellidos, email, contraseña) en una sola operación atómica.

#### Scenario: Successful registration
- **WHEN** un visitante envía nombre de escuela, datos del administrativo y una contraseña válida
- **THEN** se crea la escuela y el usuario con rol administrativo asociado a ella, y el visitante puede iniciar sesión

#### Scenario: Email already used
- **WHEN** el email ya existe en cualquier escuela
- **THEN** el sistema rechaza el registro con error de validación y no crea la escuela

#### Scenario: Atomicity
- **WHEN** falla la creación del usuario
- **THEN** no queda creada la escuela
