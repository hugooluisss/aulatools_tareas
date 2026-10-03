# Design

## Decisiones

1. **Profesor del vínculo = copia al crear.** `enrollment_subject_bindings.teacher_id` (BIGINT UNSIGNED NULL, FK `users(id)` `ON DELETE SET NULL`, índice). Se guarda el valor y no se deriva del grupo, porque la reasignación por conjunto debe sobrevivir a cambios del grupo.
2. **Valor por defecto**: `COALESCE(groups.teacher_id, subjects.teacher_id)` con el grupo de la inscripción. Los grupos existentes no tienen profesor, así el comportamiento actual se conserva. Todos los `INSERT IGNORE INTO enrollment_subject_bindings` (`EnrollmentRepository` x2, `SubjectBindingRepository` x2, `GroupRepository::replaceSubjects`) hacen join a `enrollments`/`groups`/`subjects` y rellenan `teacher_id` en el mismo `INSERT … SELECT`; sin consultas en bucle.
3. **`groups.teacher_id` opcional** (NULL), validado como `role = 'teacher'` de la misma escuela (reutilizar `teacherBelongsToSchool` análogo al de `SubjectRepository`). Se acepta en crear y actualizar grupo y se devuelve en `list`/`find`.
4. **Reasignación masiva**: `PUT /subjects/{id}/students/teacher`, solo admin, cuerpo `{cycle_id, student_ids[], teacher_id}`. Un solo `UPDATE … JOIN enrollments … WHERE subject_id, cycle_id, student_id IN (…)`. Valida materia, ciclo activo, profesor de la escuela y que todos los estudiantes tengan vínculo (404/422/400 con el estilo de `EnrollmentException` de `SubjectBindingService`). Va en `SubjectBindingService`/`Controller`.
5. **Migración**: nueva `M26100300000000AddTeachersToGroupsAndBindings` con `down`; backfill `UPDATE enrollment_subject_bindings b JOIN subjects s … SET b.teacher_id = s.teacher_id`.
6. **Fuera de alcance**: autorización por profesor del vínculo (sigue en `subjects.teacher_id`). Se documenta como siguiente cambio.

## Riesgos

- El profesor del vínculo se muestra pero aún no concede acceso: puede confundir hasta el siguiente cambio.
