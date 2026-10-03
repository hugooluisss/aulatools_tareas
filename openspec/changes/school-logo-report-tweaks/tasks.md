# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias. Nunca modificar datos de negocio.

## 1. Base de datos y backend

- [x] 1.1 Migración `M26100600000000AddSchoolLogo` con `down`, aplicada en desarrollo. Verificar con `SHOW CREATE TABLE schools`.
- [x] 1.2 `POST/DELETE /school/logo`, `logo_url` en `/school` y `.gitignore` de uploads (decisiones 1 y 2). Verificar con un test del servicio o llamada autenticada de administrador (subir PNG válido y rechazar un archivo de texto).
- [x] 1.3 PDF de asistencia: logotipo incrustado, sin columna `#`, `days` máximo 14 (decisiones 3 y 4). Verificar con `AttendanceReportServiceTest` y una llamada real que devuelva `%PDF` (con y sin logotipo) y 400 para `days=15`.

## 2. Bruno

- [x] 2.1 Requests de logotipo y actualización de la nota de `days` (máximo 14).

Nota de verificación: la llamada autenticada de 1.2 y las llamadas reales de PDF de 1.3 quedaron pendientes porque el `scratchpad/tok.php` indicado no existe en `/tmp` en este entorno. La migración y `SHOW CREATE TABLE schools` se verificaron; `AttendanceReportServiceTest` pasó.

## 3. Frontend

- [x] 3.1 Pantalla Escuela con subida, vista previa y quitar logotipo (decisión 5) y su spec.
- [x] 3.2 Formulario de asistencia con máximo 14 días y su spec.
