# Proposal

## Why

Hoy la inscripción a ciclo (`inscriptions`) y la inscripción a materia (`enrollments`) repiten estudiante y ciclo y solo se relacionan por convención (`source_group_id`). Eso permite estados inconsistentes y obliga a lógica de sincronización frágil. Además hay nombres en español en el modelo (`clave`) y el nombre `inscriptions` no coincide con el término del dominio, `enrollment`.

## What Changes

- La tabla `inscriptions` pasa a llamarse **`enrollments`**: inscripción del estudiante en un ciclo y grupo (`id`, `student_id`, `cycle_id`, `group_id`, `created_at`, `UNIQUE (student_id, cycle_id)`).
- La tabla `enrollments` actual (estudiante, materia, ciclo, `source_group_id`) se **reemplaza** por **`enrollment_subject_bindings`** (`enrollment_id` con borrado en cascada, `subject_id`, `UNIQUE (enrollment_id, subject_id)`). Sin columna `source` ni `source_group_id`; estudiante y ciclo se derivan de la inscripción.
- **BREAKING**: quitar una inscripción borra todas sus materias, incluidas las agregadas de forma individual (antes se conservaban).
- Al agregar materias a un grupo se agregan solo a los estudiantes inscritos en él; **quitar una materia de un grupo no la quita de los estudiantes**. El cambio de grupo de una inscripción reemplaza las materias del grupo anterior y conserva las que no pertenecían a él.
- **BREAKING (API/DB)**: el campo `clave` pasa a **`code`** en `subjects` y `study_plans` (columna, unicidad, cuerpos y respuestas de la API, Bruno, tipos del frontend). La etiqueta de pantalla sigue siendo "Clave".
- **BREAKING**: se elimina la ruta heredada `POST /groups/{id}/students`, que inscribía a un grupo sin crear inscripción de ciclo; la inscripción a grupos ocurre solo al crear una inscripción.
- Las rutas `/inscriptions…` y `/subjects/{id}/students…` y los textos de pantalla no cambian. Los módulos PHP `Inscriptions` y `Enrollments` se reorganizan (ver design.md).
- Las consultas de entregas, tareas, calendario, materias y reinscripciones pasan a usar los bindings.
- Migración que conserva los datos de desarrollo: las 5 inscripciones y las materias 39 a 46 sobreviven; las inscripciones a materia se convierten en bindings.

## Capabilities

### New Capabilities
- `enrollment-subject-bindings`: modelo de inscripción a ciclo (`enrollments`) y vínculos con materias (`enrollment_subject_bindings`), con sus reglas de alta, baja, cambio de grupo y sincronización.
- `naming-code-field`: el campo único de materias y planes se llama `code` en todo el sistema.

### Modified Capabilities

(Ninguna: los specs principales aún no existen. Este cambio sustituye los requisitos de modelo de los cambios `student-cycle-enrollment` y `study-plans-subject-catalog`, ambos implementados y sin archivar, que se citan en design.md.)

## Impact

- **Base de datos**: migración nueva (renombrar `inscriptions`, crear `enrollment_subject_bindings`, convertir y borrar el `enrollments` antiguo, renombrar `clave` a `code`).
- **Backend (Yii3)**: `Enrollments` e `Inscriptions` (se fusionan), `Subjects`, `Groups`, `Tasks`, `TaskComments`, `Calendar`, `Users` (consulta de inscripción vigente), `StudyPlans`; tests.
- **API**: campo `code`; ruta `POST /groups/{id}/students` eliminada.
- **Frontend**: `clave` a `code` en servicios, tipos, formularios y specs; sin cambios de URL ni textos.
- **Bruno**: campo `code`; se elimina la request de `POST /groups/{id}/students`.
- **Datos**: se conservan; verificación de 7 bindings por estudiante.
