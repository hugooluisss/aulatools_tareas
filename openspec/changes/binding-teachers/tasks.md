# Tasks

Política de pruebas para cada tarea: verificar solo lo que cambia la propia tarea, sin pruebas adicionales ni exploratorias. Nunca borrar ni modificar datos de negocio salvo lo que la tarea indique.

## 1. Base de datos

- [x] 1.1 Crear la migración `M26100300000000AddTeachersToGroupsAndBindings` (decisiones 1, 3 y 5) con `down` y aplicarla en desarrollo. Verificar con `SHOW CREATE TABLE` de `groups` y `enrollment_subject_bindings` y que ningún vínculo tenga `teacher_id` NULL.

## 2. Backend

- [x] 2.1 `Groups`: aceptar, validar y devolver `teacher_id` en crear, editar, listar y ver (decisión 3). Verificar con tests de `GroupServiceTest`.
- [x] 2.2 Rellenar `teacher_id` con `COALESCE(groups.teacher_id, subjects.teacher_id)` en los 5 `INSERT … SELECT` de vínculos (decisión 2). Verificar con tests de los servicios de inscripción y de vínculos.
- [x] 2.3 Endpoint `PUT /subjects/{id}/students/teacher` (ruta, controlador, servicio, repositorio) y `teacher_id` en `GET /subjects/{id}/students` (decisión 4). Verificar con tests de `SubjectBindingServiceTest` de los escenarios Reassign, Student without binding y Not an admin.

## 3. Bruno y frontend

- [x] 3.1 Bruno: request del endpoint nuevo y `teacher_id` en las de grupos.
- [x] 3.2 Frontend: selector de profesor en el formulario de grupos (`groups.service.ts`, `admin-catalog`) y su spec. Verificar con el spec del componente.
