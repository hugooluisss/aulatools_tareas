# Proposal

## Why

Los profesores, estudiantes y la escuela solo guardan lo mínimo (nombre y correo). Administración necesita datos de contacto: dirección, teléfonos, tutor del estudiante y fotografía del profesor.

## What Changes

- **Profesores** (`users` con rol `teacher`): nuevos campos opcionales `address`, `phone` y fotografía (`photo_url`). El celular muestra un enlace de WhatsApp (`https://wa.me/<dígitos>`) en la lista y en el formulario. Email y contraseña ya existen; se revisa que el formulario los muestre y edite correctamente.
- **Fotografía**: se sube como archivo, con recorte y ajuste en el navegador (componente `ngx-image-cropper`, recorte cuadrado), y se guarda en el disco del backend (`public/uploads/teachers/`). Nuevos endpoints `POST` y `DELETE /users/teachers/{id}/photo`.
- **Escuela** (`schools`): nuevos campos `address`, `phone`, `email` (junto a `name`); pantalla Escuela con los cuatro campos.
- **Estudiantes** (`students`): nuevos campos `address`, `contact_phone`, `guardian_name`, `guardian_phone`.
- Todos los campos nuevos son opcionales (los datos existentes se conservan) y se devuelven en las respuestas actuales.
- Los formularios de profesores y estudiantes del catálogo administrativo incluyen los campos nuevos.

## Capabilities

### New Capabilities
- `contact-data`: datos de contacto de profesores, estudiantes y escuela, y fotografía del profesor.

### Modified Capabilities

(Ninguna.)

## Impact

- **Base de datos**: migración nueva (columnas en `users`, `students`, `schools`).
- **Backend**: `Users`, `Schools`, ruta de fotografía, `.gitignore` de `public/uploads/`; tests.
- **Bruno**: campos nuevos y requests de fotografía.
- **Frontend**: dependencia `ngx-image-cropper`, formularios de profesor, estudiante y escuela, servicios y specs.
