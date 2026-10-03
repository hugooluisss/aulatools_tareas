# Tasks

Política de pruebas para cada tarea: verificar solo lo que cambia la propia tarea (con autenticación si la llamada la necesita), sin pruebas adicionales ni exploratorias. Nunca borrar ni modificar datos de negocio salvo lo que la tarea indique.

## 1. Base de datos

- [x] 1.1 Crear la migración nueva (sin editar anteriores) según la decisión 1: reinicio de filas dependientes de materias, tabla `study_plans`, `subjects` sin `cycle_id` y con `school_id`, `clave`, `plan_id`, `status` active/inactive y `UNIQUE (school_id, clave)`, `cycle_id` en `enrollments` (PK incluye ciclo) y en `tasks`, y `down` sin restaurar datos. Verificar con `SHOW CREATE TABLE` de `study_plans`, `subjects`, `enrollments` y `tasks`.
- [x] 1.2 Aplicar la migración en desarrollo desde el contenedor del backend y registrarla en la tabla `migration`. Verificar con un `SELECT` de la fila de historial.

## 2. Backend: planes y materias

- [x] 2.1 Crear el módulo `StudyPlans` con `GET/POST /study-plans` y `GET/PUT/DELETE /study-plans/{id}` (clave única 409, borrar con materias 409, solo administrador, `JsonResponse`). Verificar con tests del servicio y una llamada autenticada de profesor (403).
- [x] 2.2 Adaptar `Subjects` (controlador, servicio, repositorio): `clave`, `plan_id` opcional (plan activo, 422), estado active/inactive, sin ciclo, clave duplicada 409, `GET /subjects` con `clave`, `plan_name`, `status`, `students_count` (una consulta) y filtro `status`. Verificar con tests del servicio y una llamada autenticada a `GET /subjects`.
- [x] 2.3 Adaptar `Groups`: quitar la regla de materias del ciclo, exigir materias activas (400) y sincronizar por lote a los estudiantes inscritos en el grupo al cambiar sus materias (agregar y quitar solo filas originadas por el grupo). Verificar con tests del servicio.

## 3. Backend: inscripciones, tareas y calendario

- [x] 3.1 Adaptar `Enrollments`: `cycle_id` en inscribir/desinscribir materia, `enrollCycleGroup` con el ciclo del grupo, `POST /subjects/{id}/students/bulk` con `{cycle_id, student_ids}` y sus validaciones, filtro `cycle_id` en `GET /subjects/{id}/students` y en `DELETE /subjects/{id}/students/{student_id}`. Verificar con tests del servicio.
- [x] 3.2 Adaptar `Inscriptions`: la consulta de reinscribibles y la validación `reenrollment` usan el criterio de ciclo finalizado y sin inscripción activa. Verificar con tests del servicio y una llamada autenticada a `GET /inscriptions/reenrollable`.
- [x] 3.3 Adaptar `Tasks`, `TaskComments` y el hook de entregas: `cycle_id` obligatorio al crear tareas, entregas solo para inscritos en (materia, ciclo), filtro `cycle_id` en listados y consultas de estudiante/profesor. Verificar con tests del servicio de `TaskService` y `TaskDeliveryService`.
- [x] 3.4 Adaptar `Calendar` a la visibilidad por inscripciones de ciclos activos y profesor. Verificar con el test del servicio de calendario.

## 4. Frontend

- [x] 4.1 Crear `StudyPlansService` y la pantalla "Planes de estudio" (ruta, opción de menú, tabla, modal ordenado, borrar deshabilitado con materias). Verificar con `tsc` y su spec.
- [x] 4.2 Adaptar el formulario y la tabla de Materias: Clave, Plan opcional (solo activos), Estado en edición, sin ciclo; columnas Clave, Plan, Estado, Estudiantes; íconos deshabilitados (no ocultos). Verificar con `tsc` y el spec del componente.
- [x] 4.3 Adaptar el formulario de Grupos a materias activas del catálogo y el modal "Agregar estudiante" y "Ver estudiantes" con selector de ciclo (preseleccionado si hay uno solo) y llamadas con `cycle_id`. Verificar con `tsc` y el spec del componente.
- [x] 4.4 Adaptar las pantallas de tareas de profesor y estudiante: `cycle_id` al crear tareas, selector de ciclo cuando haya varios activos y filtros en listados. Verificar con `tsc` y los specs afectados.
- [x] 4.5 Ajustar tipos y servicios (`SubjectsService`, `TasksService`, `GroupsService`) y la pantalla de Reinscripciones al nuevo criterio (columnas con el ciclo finalizado). Verificar con `tsc` y `ng test`.

## 5. Bruno

- [x] 5.1 Agregar la carpeta `StudyPlans` con las requests de los cinco endpoints y actualizar las requests de Subjects (`clave`, `plan_id`, sin `cycle_id`), Groups, Enrollments (bulk con `cycle_id`), Tasks (`cycle_id`) con las mismas convenciones. Verificar con `bru run` de la request GET de planes.

## 6. Datos de desarrollo y cierre

- [x] 6.1 Borrar los ciclos duplicados 12 y 13 de la escuela 13 (SQL puntual, verificando antes que no tienen inscripciones ni grupos) y las materias antiguas ya reiniciadas por la migración. Verificar con un `SELECT` de ciclos (solo el 14 queda).
- [x] 6.2 Con Bruno: crear el plan "Plan general", 8 materias con clave (7 con plan y 1 sin plan), actualizar el grupo 11 con las 7 materias del plan. Verificar con las respuestas y un conteo de `enrollments` por estudiante (7 cada uno para los 5 estudiantes inscritos).
- [x] 6.3 Agregar al estudiante 57 a la materia sin plan en el ciclo 14 con el endpoint masivo (201) y quitarlo (204). Verificar con las respuestas y un conteo.
