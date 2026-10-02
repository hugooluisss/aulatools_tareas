# Spec Delta

## Purpose

Define cómo los tres roles se autentican con email y contraseña y cómo cambian su propia contraseña.

## ADDED Requirements

### Requirement: Login by email and password
El sistema SHALL autenticar a todos los usuarios con email y contraseña y devolver un JWT con expiración. El email es único globalmente.

#### Scenario: Valid credentials
- **WHEN** un usuario activo envía email y contraseña correctos
- **THEN** recibe un JWT que identifica al usuario, su rol y su escuela

#### Scenario: Invalid credentials
- **WHEN** el email o la contraseña son incorrectos
- **THEN** el sistema responde 401 con un mensaje genérico que no revela cuál campo falló

#### Scenario: Inactive student
- **WHEN** un estudiante con estado inactivo intenta iniciar sesión
- **THEN** el sistema rechaza el acceso

### Requirement: Password storage
El sistema MUST almacenar las contraseñas únicamente como hash seguro y nunca devolverlas en respuestas ni registros.

#### Scenario: Password never exposed
- **WHEN** cualquier endpoint devuelve datos de usuario
- **THEN** la respuesta no contiene la contraseña ni su hash

### Requirement: Own password change
Un usuario autenticado de cualquier rol SHALL poder cambiar su propia contraseña indicando la actual y la nueva.

#### Scenario: Change own password
- **WHEN** el usuario envía su contraseña actual correcta y una nueva válida
- **THEN** la contraseña se actualiza

#### Scenario: Wrong current password
- **WHEN** la contraseña actual enviada es incorrecta
- **THEN** el sistema rechaza el cambio

### Requirement: Role-based access
El sistema SHALL restringir cada endpoint según el rol del JWT y rechazar con 403 los accesos no permitidos.

#### Scenario: Forbidden role
- **WHEN** un estudiante invoca un endpoint reservado a administrativo o profesor
- **THEN** el sistema responde 403
