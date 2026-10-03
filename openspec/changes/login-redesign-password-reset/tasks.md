# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias. Nunca borrar ni modificar datos de negocio; las pruebas de recuperación usan un usuario de prueba o restauran su contraseña.

## 1. Infraestructura y base de datos

- [x] 1.1 Mailpit y variables de correo en `docker-compose.yml`, `symfony/mailer` con composer en el contenedor del backend y nota en el README (decisión 4). Verificar con `docker compose up -d mailpit` y `composer show symfony/mailer`.
- [x] 1.2 Migración `M26100700000000CreatePasswordResetTokens` (decisión 1) con `down`, aplicada en desarrollo. Verificar con `SHOW CREATE TABLE password_reset_tokens`.

## 2. Backend

- [x] 2.1 Servicio, repositorio, controlador, rutas y `PasswordResetMailer` de `forgot-password` y `reset-password` (decisiones 2, 3 y 5). Verificar con tests del servicio de los escenarios Existing email, Unknown email, Expired token, Reused token, Successful reset y Weak password (con un reloj o tiempo inyectable para la expiración de 1 hora).
- [x] 2.2 Verificar el flujo real: solicitar un correo, ver el mensaje en la API de Mailpit y restablecer; restaurar la contraseña del usuario de prueba al final.

## 3. Bruno

- [x] 3.1 Requests `auth_forgot-password_post.bru` y `auth_reset-password_post.bru`.

## 4. Frontend

- [x] 4.1 Layout compartido del diseño y rediseño del login responsivo (sección de diseño visual). Verificar con el spec del login.
- [x] 4.2 Pantallas `/forgot-password` y `/reset-password`, rutas y métodos de `AuthService` (decisión 6). Verificar con sus specs.
