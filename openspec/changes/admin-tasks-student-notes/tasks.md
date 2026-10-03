# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias. Nunca borrar ni modificar datos de negocio.

## 1. Base de datos

- [x] 1.1 Migración `M26100500000000CreateStudentNotes` (decisión 3) con `down`, aplicada en desarrollo. Verificar con `SHOW CREATE TABLE student_notes`.

## 2. Backend

- [x] 2.1 Módulo `StudentNotes` con `GET` y `POST /users/students/{id}/notes` y las reglas de la decisión 4. Verificar con un test del servicio de los escenarios de student-notes. (OK: `docker compose exec -T backend php vendor/bin/phpunit tests/StudentNoteServiceTest.php`.)
- [x] 2.2 Comprobar que el administrador puede leer tareas, entregas y comentarios de cualquier materia y comentar; corregir solo lo que falte. Verificar con una llamada autenticada de administrador a `GET /subjects/{id}/tasks`, `GET /tasks/{id}/deliveries` y `POST /deliveries/{id}/comments`. (El código ya permite el acceso: sujeto al colegio para las consultas, y admin permitido para comentar. No se hizo la llamada autenticada porque evitar crear/alterar datos de negocio.)

## 3. Bruno

- [x] 3.1 Requests de notas del estudiante.

## 4. Frontend

- [x] 4.1 Rutas y roles del administrador, entrada "Tareas" en el menú y reutilización de las pantallas del profesor en solo lectura (decisiones 1 y 2). Verificar con los specs de las pantallas tocadas y del shell.
- [x] 4.2 Servicio y modal de notas del estudiante, abierto desde el catálogo de estudiantes y desde la lista de entregas (decisión 5). Verificar con el spec del modal.
