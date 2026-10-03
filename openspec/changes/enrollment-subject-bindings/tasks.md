# Tasks

Política de pruebas para cada tarea: verificar solo lo que cambia la propia tarea (con autenticación si la llamada la necesita), sin pruebas adicionales ni exploratorias. Nunca borrar ni modificar datos de negocio salvo lo que la tarea indique.

## 1. Base de datos

- [x] 1.1 Crear la migración nueva (sin editar anteriores) según las decisiones 1 y 2: renombrar `enrollments` a `enrollments_legacy` y `inscriptions` a `enrollments` con índices y FK renombrados, crear `enrollment_subject_bindings`, convertir las filas legacy en vínculos, eliminar `enrollments_legacy` y renombrar `clave` a `code` en `subjects` y `study_plans` con sus índices únicos. Incluir `down`. Verificar con `SHOW CREATE TABLE` de `enrollments`, `enrollment_subject_bindings`, `subjects` y `study_plans`.
- [x] 1.2 Aplicar la migración en desarrollo desde el contenedor del backend y registrarla en la tabla `migration`. Verificar con un `SELECT` de la fila de historial y un conteo: 5 `enrollments` y 35 `enrollment_subject_bindings` (7 por estudiante).

## 2. Backend: módulo Enrollments y vínculos

- [x] 2.1 Mover el módulo `Inscriptions` a `Enrollments` (`EnrollmentController`, `EnrollmentService`, `EnrollmentRepository`, excepciones) con las rutas `/inscriptions…` sin cambios, usando la tabla `enrollments` y la creación de vínculos por lote (decisión 4); renombrar tests. Verificar con los tests del servicio y una llamada autenticada a `GET /inscriptions`.
- [x] 2.2 Convertir el servicio y repositorio de materias del antiguo `Enrollments` en `SubjectBindingService`, `SubjectBindingRepository` y `SubjectBindingController` (rutas `/subjects/{id}/students…`, alta masiva, baja con `cycle_id`, validaciones de la decisión 6), eliminar `POST /groups/{id}/students` y adaptar el hook de entregas. Verificar con tests del servicio y una llamada de profesor (403) y una a `GET /subjects/{id}/students?cycle_id=14` de una materia del grupo (5 estudiantes).
- [x] 2.3 Implementar el cambio de grupo de la inscripción según la decisión 3 y la baja con cascada. Verificar con tests del servicio de los escenarios Change group y Removing an enrollment.

## 3. Backend: dependencias y campo `code`

- [x] 3.1 Adaptar `Groups` (sincronización de materias agregadas por lote, sin quitar vínculos al retirar materias) y `Users` (inscripción vigente por `enrollments`). Verificar con tests del servicio de grupos y una llamada a `GET /users/students`.
- [x] 3.2 Adaptar `Subjects` (`students_count` y estudiantes desde vínculos), `Tasks`, `TaskComments` y `Calendar` a `enrollment_subject_bindings → enrollments`. Verificar con los tests de esos servicios y una llamada a `GET /subjects` (5 estudiantes en las materias del plan).
- [x] 3.3 Renombrar `clave` a `code` en `StudyPlans` y `Subjects` (validaciones, repositorios, respuestas, cuerpos) y sus tests. Verificar con los tests de esos servicios y una llamada a `GET /study-plans`.

## 4. Frontend

- [x] 4.1 Renombrar `clave` a `code` en servicios, tipos, formularios, plantillas y specs de materias y planes de estudio, manteniendo la etiqueta "Clave". Verificar con `prettier`, `tsc` y `ng test`.

## 5. Bruno

- [x] 5.1 Renombrar `clave` a `code` en las requests de `StudyPlans` y `Subjects`, eliminar la request de `POST /groups/{id}/students` y revisar docs de `Inscriptions`. Verificar con `bru run` de la request GET de planes de estudio.

## 6. Cierre

- [x] 6.1 Verificar los datos conservados: 5 inscripciones, 35 vínculos (7 por estudiante) y que las materias 39 a 46 conservan su `code`. Verificar con un `SELECT` de conteos y una llamada autenticada a `GET /subjects`.
- [x] 6.2 Prueba corta con Bruno: agregar al estudiante 57 a la materia Artes (ciclo 14) con el endpoint masivo (201), confirmar su vínculo con un conteo y quitarlo (204). Verificar con las respuestas y el conteo final.
