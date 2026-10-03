# Design

## Diseño visual (login y recuperación)

- Variables existentes de `frontend/src/styles/_variables.scss` (`$primary` #5b5694, `$sidebar` #3b3668, `$success` #5ba25f, `$lavender`, `$brand-accent`, `$body-text`, `$card-radius`, espaciados). No se agregan colores nuevos salvo derivados (degradado `$sidebar` → `$primary`).
- Estructura: contenedor de pantalla completa con degradado; tarjeta de ancho máximo ~920 px en dos columnas (marca | formulario). Marca: círculo claro con sombra suave y el texto "aulatools" (fuente grande) y "Tareas" debajo, como sustituto del logotipo (un solo lugar para reemplazarlo después, por ejemplo un componente `auth-brand`).
- Campos: `form-control` redondeados tipo píldora con icono de Bootstrap Icons (`bi-envelope`, `bi-lock`), fondo gris claro; botón `Entrar` verde `$success` de ancho completo en píldora; enlace "¿Olvidaste tu contraseña?" centrado debajo; enlace "Registrar una escuela" se conserva.
- Móvil (< 768 px): una columna, marca arriba compacta (círculo pequeño, sin sombra pesada), tarjeta con márgenes laterales de 16 px, sin scroll horizontal, objetivos táctiles de al menos 44 px.
- Estilos compartidos en `_auth-form.scss`/componente `auth-layout` reutilizado por login, `forgot-password` y `reset-password`; no tocar la pantalla de registro de escuela.

## Decisiones de recuperación

1. **Tabla** `password_reset_tokens`: `id`, `user_id` (FK `users` ON DELETE CASCADE), `token_hash CHAR(64)` único (SHA-256 del token), `expires_at DATETIME`, `used_at DATETIME NULL`, `created_at`. Migración `M26100700000000CreatePasswordResetTokens` con `down`.
2. **Emisión**: token aleatorio `bin2hex(random_bytes(32))`; `expires_at = ahora UTC + 1 hora`; al emitir se invalidan (marcan usados) los tokens pendientes del mismo usuario; solo se guarda el hash. Respuesta siempre 200 `{message}` igual exista o no el correo; si no existe no se envía correo. Si el envío falla se registra el error y se responde igual (sin revelar el fallo).
3. **Restablecer**: `POST /auth/reset-password` busca por hash, exige `used_at IS NULL` y `expires_at > UTC_TIMESTAMP()`; si no, 400 con mensaje genérico "Invalid or expired token."; contraseña mínima de 8; actualiza el hash con `PasswordHasherInterface`, marca el token usado e invalida los demás pendientes, todo en una transacción (`TransactionRunner`). Comparación por hash en consulta, sin comparación de texto plano.
4. **Correo**: `PasswordResetMailer` depende de `EmailSender` y del value object `EmailMessage` en `Shared\\Mail`; solo `SymfonyEmailSender` conoce Symfony Mailer/Mime y traduce errores a `MailException`. `MAILER_DSN` y `MAIL_FROM` se leen desde configuración; `APP_URL` construye `{APP_URL}/reset-password?token=<token>` y el correo indica vigencia de 1 hora. Mailpit en `docker-compose.yml` para desarrollo (`axllent/mailpit`, SMTP 1025, interfaz 8025) y valores predeterminados vía `${VAR:-valor}`; documentar en README.
5. **Rutas** públicas (sin autenticación) igual que `/auth/login`. Sin límite de intentos en este alcance (riesgo conocido; se anota en el README).
6. **Frontend**: `AuthService.forgotPassword(email)` y `resetPassword(token, password)`; `/forgot-password` muestra siempre el mensaje de confirmación; `/reset-password` lee `token` de la URL, valida coincidencia de contraseñas y mínimo 8, muestra error si el token no sirve y redirige a `/login` con aviso al terminar.

## Riesgos

- Sin límite de intentos ni captcha en `forgot-password`.
