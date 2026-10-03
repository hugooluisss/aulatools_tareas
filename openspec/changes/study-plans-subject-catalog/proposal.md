# Proposal

## Why

Hoy cada materia vive dentro de un ciclo y no tiene clave, por lo que no se puede reutilizar entre ciclos ni agrupar en un plan de estudios. La escuela necesita un catálogo de materias con clave única, planes de estudio y materias sin plan (por ejemplo extraescolares), cursadas en distintos ciclos sin mezclar inscripciones ni tareas.

## What Changes

- Nuevo catálogo **Planes de estudio** (`study_plans`: clave única por escuela, nombre, estado activo/inactivo) con CRUD y opción de menú para el administrador. Un plan con materias no se puede borrar, solo desactivar (409).
- Las materias pasan a ser un **catálogo desacoplado de los ciclos**: se elimina `subjects.cycle_id`; cada materia tiene `clave` obligatoria y única por escuela, `plan_id` opcional, profesor y estado `active`/`inactive` (reemplaza `in_progress`/`finished`). Las listas y selectores muestran solo materias activas.
- **BREAKING**: los grupos conservan su ciclo pero ya no exigen que sus materias pertenezcan a él; un grupo elige materias activas del catálogo. Al cambiar las materias de un grupo se sincronizan los estudiantes inscritos en ese grupo.
- **BREAKING**: la inscripción a materia (`enrollments`) y las tareas (`tasks`) llevan **ciclo**: `enrollments` pasa a `(student_id, subject_id, cycle_id)` y cada tarea pertenece a `(subject_id, cycle_id)`. Las pantallas y consultas filtran por ciclo (por defecto, los ciclos activos).
- El alta individual de estudiantes a una materia (`POST /subjects/{id}/students/bulk`) recibe `cycle_id`; el modal pide el ciclo cuando hay más de un ciclo activo.
- **BREAKING**: cambia el criterio de **reinscribible**: estudiante activo cuya última inscripción es de un ciclo `finished` y que no tiene inscripción en un ciclo activo (sustituye "todas las materias finalizadas" del cambio `student-cycle-enrollment`).
- Datos de desarrollo: se borran las materias actuales y los ciclos duplicados 12 y 13 con sus filas dependientes, y se crean materias nuevas asociadas a un plan, con Bruno.
- Frontend: formulario de materias con clave y plan (sin ciclo), formulario de grupos con materias activas del catálogo, nueva pantalla **Planes de estudio**, y pantallas de tareas con filtro por ciclo.
- Catálogo de Bruno: carpeta `StudyPlans` y requests de materias, grupos, inscripciones y tareas actualizadas.

## Capabilities

### New Capabilities
- `study-plans`: catálogo de planes de estudio (clave, nombre, estado) y su pantalla.
- `subject-catalog`: materias como catálogo (clave única, plan opcional, estado activo/inactivo, sin ciclo) y reglas de grupos con materias activas.
- `cycle-scoped-enrollments`: inscripciones a materia y tareas por ciclo, alta individual con ciclo, sincronización de grupos y nuevo criterio de reinscribible.

### Modified Capabilities

(Ninguna: los specs principales aún no existen; los requisitos de `cycle-enrollments` del cambio `student-cycle-enrollment` que aquí cambian se reemplazan en estos capabilities y se indican en design.md.)

## Impact

- **Base de datos**: migración nueva (`study_plans`; `subjects` sin `cycle_id`, con `clave`, `plan_id`, estado nuevo; `enrollments` y `tasks` con `cycle_id`), con reinicio destructivo de datos de desarrollo dependientes de materias.
- **Backend (Yii3)**: módulo nuevo `StudyPlans`; cambios en `Subjects`, `Groups`, `Enrollments`, `Inscriptions`, `Tasks`, `TaskComments` y `Calendar`.
- **API**: `/study-plans` (CRUD), cuerpos y respuestas de `/subjects`, `/groups`, `/tasks`, `POST /subjects/{id}/students/bulk` con `cycle_id`, filtro `cycle_id` en listados de tareas y estudiantes de materia.
- **Frontend (Angular 22, signals)**: `admin-catalog`, pantalla de Planes de estudio, menú, pantallas de materias/tareas de profesor y estudiante.
- **Bruno**: `StudyPlans` nueva y requests actualizadas.
- **Datos**: se borran las materias 23 a 38 y los ciclos 12 y 13 de la escuela 13 y sus dependencias de desarrollo.
