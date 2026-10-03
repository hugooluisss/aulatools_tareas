# Proposal

## Why

El login es una tarjeta simple sin recuperación de contraseña. Se rediseña con una tarjeta dividida (marca a la izquierda, formulario a la derecha), adaptada a móviles y a los colores de la aplicación, y se agrega la recuperación de contraseña por correo.

## What Changes

- **Login rediseñado**: fondo con degradado de los colores de la app, tarjeta blanca redondeada; panel izquierdo con la marca (texto "aulatools" y "Tareas" como logotipo provisional); panel derecho con título, campos con icono y forma de píldora (correo, contraseña), botón verde "Entrar" y enlace "¿Olvidaste tu contraseña?". En pantallas pequeñas la marca pasa arriba, compacta, y el formulario ocupa todo el ancho.
- **Recuperar contraseña por correo**:
  - Pantalla `/forgot-password` (pide el correo) y pantalla `/reset-password?token=…` (nueva contraseña y confirmación), con el mismo diseño del login.
  - `POST /auth/forgot-password` `{email}`: siempre responde 200 con el mismo mensaje (no revela si el correo existe); si existe, genera un token y envía el enlace por correo.
  - `POST /auth/reset-password` `{token, password}`: valida el token y cambia la contraseña (mínimo 8 caracteres).
  - **El token expira en 1 hora**, es de un solo uso y los anteriores del mismo usuario se invalidan al emitir uno nuevo. Solo se guarda su hash.
- Envío de correo con `symfony/mailer` configurado por `MAILER_DSN`, `MAIL_FROM` y `APP_URL`; en desarrollo se agrega Mailpit al `docker-compose.yml`.

## Capabilities

### New Capabilities
- `login-redesign`: nuevo diseño responsivo del login y de las pantallas de recuperación.
- `password-reset`: recuperación de contraseña por correo con token de 1 hora.

### Modified Capabilities

(Ninguna.)

## Impact

- **Base de datos**: migración nueva `password_reset_tokens`.
- **Backend**: `Auth` (servicio, controlador, repositorio, rutas), servicio de correo, dependencia `symfony/mailer`, tests.
- **Infraestructura**: servicio Mailpit y variables de correo en `docker-compose.yml`/README.
- **Bruno**: dos requests nuevas.
- **Frontend**: login, dos pantallas nuevas, rutas, servicio de autenticación, estilos.
