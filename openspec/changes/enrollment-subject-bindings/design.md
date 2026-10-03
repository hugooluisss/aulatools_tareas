# Design

## Context

Estado actual (observado en el código y la base de desarrollo):

- `inscriptions` (`id`, `student_id`, `cycle_id`, `group_id`, `created_at`, `UNIQUE (student_id, cycle_id)`) y `enrollments` (`student_id`, `subject_id`, `cycle_id`, `source_group_id`, PK `(student_id, subject_id, cycle_id)`). Hay 5 inscripciones y 35 filas de `enrollments`; `task_deliveries` está vacía.
- Usan `enrollments`: `Calendar`, `Enrollments`, `Groups`, `Inscriptions`, `Subjects` y `Tasks` (repositorios). Usan `inscriptions`: `Enrollments`, `Groups`, `Inscriptions` y `Users`.
- `clave` aparece en `StudyPlans`, `Subjects`, el frontend (servicios, formularios, specs) y Bruno.
- Contrato de API: respuestas sin `data`, errores HTTP con `{"error":{"code","message"}}` vía `App\Shared\JsonResponse`; frontend Angular 22 zoneless con signals y ids como texto.
- Ver los cambios `student-cycle-enrollment` y `study-plans-subject-catalog` (implementados, sin archivar) para el modelo que este cambio reemplaza.

## Goals / Non-Goals

**Goals:**
- Un modelo normalizado: inscripción a ciclo y vínculos con materias con llaves foráneas y cascada.
- Nombres en inglés en base, código y API (`code`, `enrollments`, `enrollment_subject_bindings`).
- Conservar los datos de desarrollo.

**Non-Goals:**
- Cambiar rutas `/inscriptions…`, `/subjects/{id}/students…` o textos de pantalla.
- Distinguir el origen de una materia (grupo o individual): se decidió no tener `source`.
- Quitar materias de los estudiantes cuando se quitan del grupo.

## Decisions

1. **Esquema.** `enrollments` (renombrada desde `inscriptions`, con FKs e índices renombrados `uq_enrollments_student_cycle`, `ix_enrollments_cycle`, `ix_enrollments_group`) y `enrollment_subject_bindings` (`enrollment_id` FK `ON DELETE CASCADE`, `subject_id` FK, PK `(enrollment_id, subject_id)`, índice por `subject_id`). La tabla `enrollments` antigua se renombra a `enrollments_legacy` antes de renombrar `inscriptions`, se convierte y se elimina.
2. **Migración con datos.** Una migración nueva: (a) `RENAME TABLE enrollments TO enrollments_legacy, inscriptions TO enrollments`; (b) renombrar índices/FK; (c) crear `enrollment_subject_bindings`; (d) `INSERT INTO enrollment_subject_bindings SELECT e.id, l.subject_id FROM enrollments_legacy l INNER JOIN enrollments e ON e.student_id = l.student_id AND e.cycle_id = l.cycle_id` (filas sin inscripción correspondiente se descartan); (e) `DROP TABLE enrollments_legacy`; (f) renombrar `clave` a `code` en `subjects` y `study_plans` con sus índices únicos (`uq_subjects_school_code`, `uq_study_plans_school_code`). `down` invierte el esquema y reconstruye `enrollments_legacy` desde los bindings sin `source_group_id`.
3. **Cambio de grupo sin `source`.** Dentro de una transacción: `DELETE` de los vínculos de la inscripción cuya materia pertenece al grupo anterior (`subject_id IN (SELECT subject_id FROM group_subjects WHERE group_id = :old)`), `UPDATE` del grupo y `INSERT IGNORE … SELECT` de las materias del grupo nuevo. Los vínculos de materias fuera del grupo anterior (agregados de forma individual) se conservan. Una materia individual que también estaba en el grupo anterior se reemplaza; se acepta.
4. **Alta de inscripciones.** El servicio crea la fila de `enrollments` y los vínculos con un `INSERT IGNORE … SELECT` desde `group_subjects` (una sola sentencia por inscripción, o una por lote en el alta masiva) y genera las entregas con una sola sentencia por materia afectada a través del hook de entregas existente, adaptado para resolver estudiantes por vínculo y ciclo.
5. **Sincronización de grupos.** Al actualizar las materias de un grupo, las materias agregadas se insertan para todas las inscripciones del grupo con un `INSERT IGNORE … SELECT` por lote y se generan sus entregas; las retiradas no tocan vínculos.
6. **Alta individual a materia.** `POST /subjects/{id}/students/bulk` valida por lote (ciclo activo 422, estudiantes de la escuela 404, inscripción del estudiante en el ciclo 400, ya vinculados 409) y crea los vínculos en una transacción; `DELETE /subjects/{id}/students/{student_id}?cycle_id=` borra el vínculo de esa inscripción; `GET /subjects/{id}/students?cycle_id=` y `students_count` usan `COUNT(DISTINCT enrollments.student_id)` sobre los vínculos.
7. **Módulos PHP.** El módulo `Enrollments` pasa a ser el dueño del modelo: `EnrollmentController`, `EnrollmentService`, `EnrollmentRepository` (antes `Inscription*`, rutas `/inscriptions…` sin cambios) y `SubjectBindingService`, `SubjectBindingRepository` y `SubjectBindingController` (antes el servicio de materias del módulo `Enrollments`, rutas `/subjects/{id}/students…`). `TaskDeliveryEnrollmentHook` se mantiene. El módulo `Inscriptions` se elimina y se retiran `POST /groups/{id}/students` y su acción. Los tests se renombran con las clases.
8. **Campo `code`.** Renombrado en repositorios, servicios, validaciones, respuestas y cuerpos de `StudyPlans` y `Subjects`, en servicios, tipos, formularios y specs del frontend y en las requests de Bruno; las etiquetas visibles siguen diciendo "Clave".
9. **Consultas dependientes.** `Tasks`, `TaskComments`, `Calendar`, `Subjects`, `Groups` y `Users` reescriben sus joins de `enrollments(student_id, subject_id, cycle_id)` a `enrollment_subject_bindings → enrollments`; no se permiten consultas por fila.

## Risks / Trade-offs

- [Quitar una inscripción borra también las materias individuales] → decisión explícita del usuario; se documenta en el spec.
- [Cambio de grupo reemplaza una materia individual que estaba en el grupo anterior] → se acepta como límite del modelo sin `source`.
- [Renombrar clases y tablas en una sola migración y refactor] → se ejecuta en orden: migración, luego módulos, luego frontend/Bruno; se verifica con el conteo de 7 vínculos por estudiante.
- [La migración `down` no restaura `source_group_id`] → solo desarrollo.
- [El frontend y el backend deben desplegarse juntos por el campo `code`] → se aplican en el mismo ciclo de trabajo.

## Migration Plan

1. Aplicar la migración en desarrollo y registrarla en la tabla `migration`.
2. Desplegar backend con los módulos reorganizados y luego el frontend con `code`.
3. Verificar 5 inscripciones y 35 vínculos (7 por estudiante).
4. Rollback: `down` recrea el esquema anterior sin `source_group_id`.

## Open Questions

Ninguna que cambie las specs ni las tareas.
