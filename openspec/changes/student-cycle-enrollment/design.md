# Design

## Context

Estado actual (ver proposal.md para la motivación):

- `enrollments` guarda la inscripción por materia (`student_id`, `subject_id`, `source_group_id`). `EnrollmentService::enrollGroup` ya inscribe al estudiante en todas las materias de un grupo.
- Los grupos pertenecen a un ciclo (`groups.cycle_id`) y sus materias deben ser de ese ciclo.
- Los ciclos tienen `status` `active` / `finished` y pueden existir varios activos a la vez.
- `students.enrollment_number` es NOT NULL y único por escuela; lo leen tareas, entregas y la UI de profesores.
- Contrato de API vigente: respuestas sin envoltorio `data`, paginación en headers `X-*`, errores con código HTTP y `{"error":{"code","message"}}` mediante `App\Shared\JsonResponse`.
- Frontend zoneless: todo estado asignado de forma asíncrona usa `signal()`.

## Goals / Non-Goals

**Goals:**
- Una inscripción por estudiante y ciclo, ligada a un grupo, que sea la fuente de verdad de "inscrito en el ciclo".
- Reutilizar la lógica de inscripción por grupo en lugar de duplicarla.
- Listado de estudiantes con su inscripción vigente sin N+1.

**Non-Goals:**
- Un estado persistido "aspirante/estudiante": la condición se deriva de los datos.
- Cambiar las inscripciones individuales por materia.
- Calificaciones o aprobado/reprobado: "terminada" se deriva solo de `subjects.status = 'finished'`.
- Historial de inscripciones por ciclo en la UI (la tabla lo permite, la vista queda para después).
- Eliminar la columna `enrollment_number`.

## Decisions

1. **Tabla nueva `inscriptions`** (`id`, `student_id`, `cycle_id`, `group_id`, `created_at`) con `UNIQUE (student_id, cycle_id)` y llaves foráneas a `students(user_id)`, `academic_cycles` y `groups`. El nombre evita chocar con `enrollments` (materias).
   *Alternativa:* agregar `cycle_id`/`group_id` a `students`. Se descartó: un estudiante tendrá una inscripción por ciclo y se perdería el historial.
2. **Reuso de la inscripción por grupo.** El servicio de inscripciones llama a la lógica existente de `EnrollmentService` (inscribir en las materias del grupo y generar entregas) dentro de la misma transacción, y la baja usa un método nuevo `unenrollGroup(studentId, groupId)` que borra solo filas con `source_group_id = groupId`. Las filas individuales (`source_group_id IS NULL`) no se tocan.
   *Alternativa:* duplicar la lógica en el módulo nuevo. Se descartó por duplicación.
3. **Estado derivado.** "Inscrito en el ciclo activo" = existe inscripción cuyo ciclo tiene `status = 'active'`. `GET /users/students` hace un `LEFT JOIN` a `inscriptions`/`academic_cycles`/`groups` filtrado por ciclo activo y devuelve `enrollment: {id, cycle_id, cycle_name, group_id, group_name} | null`. Si hubiera varios ciclos activos con inscripción se devuelve la más reciente.
4. **Cambio de grupo = `PUT /inscriptions/{id}`** con `group_id` (mismo ciclo): quita las materias del grupo anterior (decisión 2) e inscribe en las del nuevo, en una transacción.
5. **Endpoints** (solo admin, bare responses): `GET /inscriptions?cycle_id&student_id` (array con estudiante, ciclo y grupo), `POST /inscriptions` (201), `PUT /inscriptions/{id}`, `DELETE /inscriptions/{id}` (204). Errores: 400 validación (grupo de otro ciclo), 404 recurso de otra escuela, 409 duplicado por `UNIQUE`, 422 ciclo finalizado.
6. **Matrícula = id.** Se mantiene la columna `enrollment_number` por compatibilidad. El alta la rellena con el id después del `INSERT` y la migración reescribe los registros existentes con `CAST(user_id AS CHAR)`. Se retira de los cuerpos de alta/edición y del formulario; las vistas que la muestran siguen leyendo la columna, que ya vale el id. El `UNIQUE` por escuela se conserva (el id es único).
   *Alternativa:* borrar la columna. Se descartó por el alcance en tareas/entregas.
7. **Frontend.** `InscriptionsService` (single y masivo) y dos pantallas nuevas con menú y rutas de administrador: **Inscripciones** (`/inscripciones`, candidatos de `GET /inscriptions/aspirants`) y **Reinscripciones** (`/reinscripciones`, candidatos de `GET /inscriptions/reenrollable`). Ambas listan candidatos con casillas, un Ciclo destino (solo activos; en reinscripciones se excluye el ciclo de la inscripción anterior de los seleccionados) y un Grupo filtrado por ciclo, y llaman a `POST /inscriptions/bulk`. La tabla de Estudiantes conserva la columna de solo lectura "Inscripción" (`Grupo · Ciclo` o "Sin inscripción") y el modal de estudiantes no gestiona inscripciones. Estado con signals; la lista se recarga tras la operación. Una pantalla comparte componente/plantilla con la otra parametrizado por tipo, para no duplicar.
8. **Candidatos con una consulta cada uno.** Aspirante: estudiante activo `NOT EXISTS` en `inscriptions`. Reinscribible: estudiante activo con inscripción previa (la más reciente por `created_at`/`id`) y `NOT EXISTS` de una materia en `enrollments` de ese estudiante con `source_group_id` igual al grupo de esa inscripción y `subjects.status <> 'finished'`. Ambos devuelven un arreglo plano que incluye, para reinscripciones, el ciclo y grupo de la última inscripción.
9. **Alta masiva atómica** (`POST /inscriptions/bulk`, `{type: 'enrollment'|'reenrollment', student_ids, cycle_id, group_id}`): valida primero ciclo (activo, de la escuela), grupo (del ciclo) y todos los estudiantes con consultas por lote (sin N+1), y solo si todo es válido crea las inscripciones dentro de una transacción reutilizando la lógica de creación individual. Errores: 400 validación, 404 recurso ajeno, 409 estudiante ya inscrito en el ciclo destino o ciclo destino igual al anterior, 422 estudiante no elegible para el tipo o ciclo finalizado; el mensaje lista los ids problemáticos.
10. **Alta masiva a una materia** (`POST /subjects/{id}/students/bulk`, `{student_ids}`, solo administrador): valida por lote que la materia exista y su ciclo esté activo (422), que todos los estudiantes sean de la escuela (404), tengan inscripción en el ciclo de la materia (400) y no estén ya en la materia (409), con los ids en el mensaje; crea las inscripciones individuales (`source_group_id` nulo) en una transacción reutilizando la lógica de `enrollSubject` (incluye las filas de entrega). El modal usa `GET /inscriptions?cycle_id=` y `GET /subjects/{id}/students` para armar candidatos, sin endpoint nuevo de listado.
   *Alternativa:* una llamada por estudiante desde el frontend. Se descartó: no es atómica y multiplica las peticiones.

## Risks / Trade-offs

- [Una materia finalizada pero no aprobada permite reinscribir] → el sistema no modela aprobación; se acepta como alcance actual.
- [Una materia puede tener estudiantes individuales que no vienen de un grupo] → es intencional (extraescolares); la baja de una inscripción de ciclo no los toca.
- [Varios ciclos activos a la vez] → el listado muestra la inscripción más reciente; se documenta y se podría restringir a un ciclo activo en otro cambio.
- [Baja borra filas de `enrollments` y posibles entregas ligadas] → la baja solo toca filas con origen en el grupo; las entregas se conservan por la regla existente de bajas de materia (verificar en la implementación que no se pierden calificaciones; si hubiera riesgo, bloquear la baja con 422).
- [Reescribir `enrollment_number` pierde las matrículas A001…] → es decisión explícita del usuario; la migración no es reversible sin respaldo, por lo que `down` no las restaura.
- [Estudiantes existentes quedan "Sin inscripción"] → esperado; el administrativo los inscribe desde la pantalla Inscripciones.

## Migration Plan

1. Migración nueva: crear `inscriptions`; `UPDATE students SET enrollment_number = user_id`.
2. Registrar la migración en la tabla `migration` (el runner del proyecto no imprime salida; verificar la fila).
3. Desplegar backend, luego frontend (el frontend viejo seguiría enviando `enrollment_number`; el backend lo ignora).
4. Rollback: `down` elimina `inscriptions`; las matrículas originales no se restauran.

## Open Questions

- ¿Debe haber un solo ciclo activo a la vez? No bloquea este cambio.
