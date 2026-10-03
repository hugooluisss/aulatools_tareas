# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias. Nunca borrar ni modificar datos de negocio salvo lo que la tarea indique.

## 1. Base de datos

- [x] 1.1 Crear la migración `M26100400000000AddContactData` (decisión 1) con `down` y aplicarla en desarrollo. Verificar con `SHOW CREATE TABLE` de `users`, `students` y `schools`.

## 2. Backend

- [x] 2.1 Escuela: aceptar, validar y devolver `address`, `phone`, `email` en `GET/PUT /school` (decisiones 2 y 5). Verificar con el test del servicio de escuela o `UserServiceTest` según corresponda.
- [x] 2.2 Usuarios: campos de profesor (`address`, `phone`, `photo_url`) y de estudiante (`address`, `contact_phone`, `guardian_name`, `guardian_phone`) en crear, editar, listar y ver, con la validación de la decisión 2. Verificar con `UserServiceTest` (escenarios Save teacher phone, Invalid phone, Edit without password, Save guardian).
- [x] 2.3 Fotografía: `POST/DELETE /users/teachers/{id}/photo`, borrado del archivo al reemplazar o eliminar al profesor, `.gitignore` de `backend/public/uploads/` (decisión 4). Verificar con un test del servicio para tipo inválido, tamaño y reemplazo.

## 3. Bruno

- [x] 3.1 Campos nuevos en las requests de profesores, estudiantes y escuela, y requests de fotografía.

## 4. Frontend

- [x] 4.1 Pantalla Escuela con nombre, dirección, teléfono y correo (`school.service.ts`, `school.component.ts`) y su spec.
- [x] 4.2 Formularios de profesor y estudiante en `admin-catalog` con los campos nuevos y enlace de WhatsApp (decisiones 3 y 6); revisar email y contraseña del profesor. Verificar con el spec del componente.
- [x] 4.3 Fotografía: instalar `ngx-image-cropper`, modal de recorte 1:1 en el formulario del profesor, subida y quitar (decisión 4), con su spec. Verificar con el spec del componente.
