# Tasks

Política de pruebas para cada tarea: verificar solo lo que cambia la propia tarea (con autenticación si la llamada la necesita), sin pruebas adicionales ni exploratorias.

## 1. Base de datos

- [x] 1.1 Crear la migración nueva (sin editar las anteriores) con la tabla `inscriptions` (`id`, `student_id`, `cycle_id`, `group_id`, `created_at`, `UNIQUE (student_id, cycle_id)`, llaves foráneas). Verificar con `SHOW CREATE TABLE inscriptions`.
- [x] 1.2 En la misma migración, reescribir `students.enrollment_number` con el id del estudiante. Verificar con un `SELECT` sobre los 5 estudiantes de la escuela 13 (matrícula = id).
- [x] 1.3 Aplicar la migración en la base de desarrollo desde el contenedor del backend y registrarla en la tabla `migration`. Verificar con un `SELECT` de la fila de historial.

## 2. Backend: inscripciones

- [x] 2.1 Agregar `unenrollGroup(studentId, groupId)` al repositorio/servicio de inscripciones por materia (borra solo filas con `source_group_id = groupId`). Verificar con un test de PHPUnit del servicio.
- [x] 2.2 Crear el módulo `Inscriptions` (controlador, servicio, repositorio) con `POST /inscriptions`: valida estudiante y ciclo de la escuela, ciclo activo, grupo del ciclo, unicidad; reutiliza la inscripción por grupo en una transacción. Verificar con tests del servicio para 201, 400, 404, 409 y 422.
- [x] 2.3 Implementar `PUT /inscriptions/{id}` (cambio de grupo del mismo ciclo) y `DELETE /inscriptions/{id}` (204, conserva materias individuales). Verificar con tests del servicio de los escenarios Change group y Remove enrollment.
- [x] 2.4 Implementar `GET /inscriptions?cycle_id&student_id` y registrar las cuatro rutas solo para administrador con `App\Shared\JsonResponse` (sin envoltorio `data`, errores con código HTTP). Verificar con una llamada autenticada de admin y una de profesor (403).

- [x] 2.5 Agregar `GET /inscriptions/aspirants` y `GET /inscriptions/reenrollable` (arreglos planos, solo administrador, una consulta cada uno, sin N+1). Verificar con tests del servicio de los escenarios Aspirants y Re-enrollable students y una llamada autenticada a cada uno.
- [x] 2.6 Agregar `POST /inscriptions/bulk` (`type`, `student_ids`, `cycle_id`, `group_id`): validación por lote de ciclo, grupo, elegibilidad según `type`, estudiante ya inscrito en el ciclo destino y ciclo igual al anterior; creación atómica reutilizando la lógica individual; 201 con las inscripciones y errores 400/404/409/422 con los ids problemáticos. Verificar con tests del servicio de los escenarios de alta masiva, reinscripción masiva y ciclo destino ya inscrito.
- [x] 2.7 Registrar las tres rutas nuevas solo para administrador con `App\Shared\JsonResponse`. Verificar con una llamada autenticada de administrador y una de profesor (403).
- [x] 2.8 Agregar `POST /subjects/{id}/students/bulk` (`student_ids`) con validación por lote (ciclo activo 422, estudiantes de la escuela 404, inscripción en el ciclo de la materia 400, ya inscritos 409, ids en el mensaje) y creación atómica reutilizando la lógica de `enrollSubject`. Verificar con tests del servicio de cada escenario de Add students to a subject.
- [x] 2.9 Registrar la ruta solo para administrador con `App\Shared\JsonResponse`. Verificar con una llamada autenticada de profesor (403).
- [x] 2.10 Hacer que borrar una materia con estudiantes inscritos responda 409 `CONFLICT` y que `GET /subjects` incluya `students_count` con una sola consulta (sin N+1). Verificar con un test del servicio y una llamada autenticada a `GET /subjects`.

## 3. Backend: estudiantes y matrícula

- [x] 3.1 Quitar `enrollment_number` de la validación del alta/edición de estudiantes y rellenarlo con el id tras el `INSERT`. Verificar con un test de `UserService` y un alta autenticada.
- [x] 3.2 Hacer que `GET /users/students` devuelva `enrollment` (inscripción del ciclo activo o `null`) con un solo `LEFT JOIN`, sin consultas por fila. Verificar con una llamada autenticada que muestra un estudiante inscrito y otro sin inscripción.

## 4. Frontend

- [x] 4.1 Crear `InscriptionsService` (list, create, update, remove) con respuestas sin `data`. Verificar con su spec.
- [x] 4.2 Quitar el campo Matrícula del formulario y de las llamadas de estudiantes. Verificar con `tsc` y el spec afectado.
- [x] 4.3 Quitar del modal de estudiantes la sección Inscripción y su lógica (estado, `saveEnrollment`, `removeEnrollment`); la tabla conserva la columna de solo lectura. Verificar con `tsc` y el spec del componente.
- [x] 4.4 Agregar a la tabla la columna Inscripción (`Grupo · Ciclo` o "Sin inscripción"). Verificar con el spec del componente.
- [x] 4.5 Mostrar el id como matrícula donde hoy se lee `enrollment_number` en las vistas de profesores. Verificar con `tsc` y los specs afectados.

- [x] 4.6 Agregar a `InscriptionsService` los métodos de aspirantes, reinscribibles y alta masiva (respuestas sin `data`). Verificar con su spec.
- [x] 4.7 Crear las pantallas Inscripciones (`/inscripciones`) y Reinscripciones (`/reinscripciones`) con un componente compartido parametrizado por tipo: lista con casillas, Ciclo destino (solo activos; en reinscripciones sin el ciclo anterior de los seleccionados), Grupo filtrado, botón "Inscribir seleccionados" / "Reinscribir seleccionados", recarga tras la operación y signals. Verificar con `tsc` y un spec del componente.
- [x] 4.8 Agregar las rutas (solo administrador) y las opciones Inscripciones y Reinscripciones al menú del administrador. Verificar con `tsc` y el spec del menú.
- [x] 4.9 Reemplazar en Materias el selector por fila por el botón "Agregar estudiante" (deshabilitado si el ciclo está finalizado) que abre un modal con casillas de los estudiantes inscritos en el ciclo de la materia, sin los ya inscritos, y "Agregar seleccionados" que llama al endpoint masivo y recarga; signals, ids normalizados con `Number()`. Verificar con `tsc` y un spec del componente.
- [x] 4.10 Quitar de Grupos el selector de estudiante y el botón de inscribir. Verificar con `tsc` y el spec del componente.
- [x] 4.11 Diagnosticar por qué el ícono "Agregar estudiante" de Materias no abre el modal y corregirlo; reproducirlo primero con un spec que renderice el componente. Verificar con ese spec y `ng test`.
- [x] 4.12 Agregar la entrada `disabled` a `app-icon-button`; en Materias mostrar "Agregar estudiante" deshabilitado (no oculto) con ciclo finalizado y el borrado deshabilitado cuando `students_count > 0`; convertir "Ver estudiantes" en un modal de solo lectura. Verificar con `tsc` y el spec del componente.

## 5. Bruno

- [x] 5.1 Agregar la carpeta `Inscriptions` con las cuatro requests (bodies activos con `body: json`, `auth: inherit`, aserciones válidas) y actualizar las requests de estudiantes sin `enrollment_number`. Verificar con `bru run` de la request GET de inscripciones usando el token de `local.bru`.

- [x] 5.2 Agregar a la carpeta `Inscriptions` las requests de `GET /inscriptions/aspirants`, `GET /inscriptions/reenrollable` y `POST /inscriptions/bulk` con las mismas convenciones. Verificar con `bru run` solo de las dos requests GET.
- [x] 5.3 Agregar a `bruno/` la request `POST /subjects/{id}/students/bulk` con las mismas convenciones. Verificar solo con una ejecución de la request en la prueba 6.3.

## 6. Cierre

- [x] 6.1 Con los datos del Ciclo 14 y el Grupo 1A, inscribir un estudiante desde Bruno y confirmar que queda en las 7 materias, y que su baja las quita. Verificar con las respuestas de esas dos llamadas.
- [x] 6.2 Inscribir en lote a 2 aspirantes en el Ciclo 14 y el Grupo 1A desde Bruno y confirmar 7 materias por estudiante; reinscribir no se prueba con datos reales (no hay reinscribibles) salvo con los tests del servicio. Verificar con las respuestas y un conteo en `enrollments`.
- [x] 6.3 Con una materia del Ciclo 14 y un estudiante inscrito en ese ciclo (inscribirlo y luego removerlo desde Bruno), agregarlo a la materia con el endpoint masivo (201), confirmar su inscripción individual con un conteo y quitarlo con `DELETE /subjects/{id}/students/{student_id}`. Verificar con las respuestas y los conteos.
