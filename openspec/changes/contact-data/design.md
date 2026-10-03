# Design

## Decisiones

1. **Columnas** (todas NULL, sin cambio de datos existentes), migración `M26100400000000AddContactData` con `down`:
   - `users`: `address VARCHAR(255)`, `phone VARCHAR(20)`, `photo_path VARCHAR(255)`.
   - `students`: `address VARCHAR(255)`, `contact_phone VARCHAR(20)`, `guardian_name VARCHAR(180)`, `guardian_phone VARCHAR(20)`.
   - `schools`: `address VARCHAR(255)`, `phone VARCHAR(20)`, `email VARCHAR(255)`.
2. **Validación** (en el servicio, estilo de `UserService`): textos recortados con `trim`, cadena vacía se guarda como NULL; teléfonos con 10 a 15 dígitos tras quitar `+`, espacios, guiones y paréntesis (se guardan solo dígitos); correo de la escuela con `FILTER_VALIDATE_EMAIL`. Los teléfonos no incluyen país por defecto: quien captura debe escribir el código de país para que el enlace de WhatsApp funcione.
3. **WhatsApp**: solo frontend. Enlace `https://wa.me/<phone>` con `target="_blank" rel="noopener"`, visible solo si hay celular. No se guarda en base de datos.
4. **Fotografía**:
   - Recorte en el navegador con `ngx-image-cropper` (relación 1:1, salida JPEG de 400×400 vía canvas, calidad 0.85) dentro de un modal del formulario del profesor; así el archivo subido ya es pequeño.
   - `POST /users/teachers/{id}/photo` multipart, campo `photo`, solo admin, tipos `image/jpeg|png|webp` verificados con `finfo` (no por extensión), máximo 2 MB; guarda `public/uploads/teachers/{id}-{timestamp}.jpg` (o la extensión detectada), borra la foto anterior y actualiza `users.photo_path`. Devuelve el profesor.
   - `DELETE /users/teachers/{id}/photo` borra el archivo y deja `photo_path` NULL.
   - Las respuestas del profesor exponen `photo_url` (ruta `/uploads/teachers/<archivo>` o null); el frontend la resuelve contra el origen del backend (`environment.apiUrl`). `public/uploads/` se agrega a `.gitignore`. El servidor integrado de PHP sirve los archivos estáticos de `public/`.
   - Al borrar un profesor se elimina su archivo.
5. **Escuela**: `GET/PUT /school` aceptan y devuelven los cuatro campos (`name` sigue obligatorio). La pantalla Escuela pasa a un formulario con los cuatro campos.
6. **Formularios**: profesor y estudiante en `admin-catalog` reciben los campos nuevos en el modal existente; la lista de profesores muestra el celular como enlace de WhatsApp. Email y contraseña de profesor: revisar etiquetas, validaciones (contraseña mínima 8 solo al crear) y que editar no exija contraseña.

## Riesgos

- Las fotos viven en el disco del backend: respaldos y despliegues sin volumen las perderían. Se acepta para este alcance.
