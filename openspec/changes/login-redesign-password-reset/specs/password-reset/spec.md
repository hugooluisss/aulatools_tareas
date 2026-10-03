# Spec Delta

## Purpose

Recuperación de contraseña por correo con token de una hora.

## ADDED Requirements

### Requirement: Request a reset link
`POST /auth/forgot-password` SHALL responder 200 con el mismo mensaje exista o no el correo y, si existe, SHALL enviar un enlace con un token nuevo que invalida los anteriores pendientes del usuario.

#### Scenario: Existing email
- **WHEN** se solicita la recuperación de un correo registrado
- **THEN** se envía un correo con el enlace `/reset-password?token=…` y la respuesta es 200

#### Scenario: Unknown email
- **WHEN** el correo no existe
- **THEN** la respuesta es idéntica (200) y no se envía correo

### Requirement: Token expires in one hour
El token SHALL expirar 1 hora después de emitirse, SHALL ser de un solo uso y SHALL guardarse solo como hash.

#### Scenario: Expired token
- **WHEN** se usa el token pasada 1 hora
- **THEN** la API responde 400 y la contraseña no cambia

#### Scenario: Reused token
- **WHEN** se usa un token ya empleado
- **THEN** la API responde 400

### Requirement: Reset the password
`POST /auth/reset-password` con un token vigente y una contraseña de al menos 8 caracteres SHALL cambiar la contraseña y marcar el token como usado.

#### Scenario: Successful reset
- **WHEN** se envía un token vigente y una contraseña válida
- **THEN** el usuario puede iniciar sesión con la nueva contraseña y no con la anterior

#### Scenario: Weak password
- **WHEN** la contraseña tiene menos de 8 caracteres
- **THEN** la API responde 400 y el token sigue vigente
