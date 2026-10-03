# Proposal

## Why

Hoy un estudiante se da de alta y queda "suelto": no hay un registro que diga en qué ciclo y grupo está inscrito, y la matrícula se captura a mano. El administrativo necesita un flujo claro de inscripción por ciclo y grupo, y poder saber de un vistazo quién está inscrito en el ciclo activo.

## What Changes

- Nueva entidad **inscripción** (`inscriptions`): estudiante + ciclo + grupo + fecha. Un estudiante tiene como máximo una inscripción por ciclo, y el grupo MUST pertenecer a ese ciclo.
- Al inscribir, el sistema inscribe automáticamente al estudiante en todas las materias del grupo (reutiliza la lógica existente de inscripción a grupo, con `source_group_id`).
- Las inscripciones se pueden cambiar de grupo y **remover** (baja). La baja quita las inscripciones a materias que provenían de ese grupo y conserva las materias inscritas de forma individual.
- Un registro de estudiante activo es un estudiante; está "inscrito en el ciclo" solo si tiene una inscripción en un ciclo activo. El listado de estudiantes muestra su inscripción vigente (ciclo y grupo) o "Sin inscripción".
- **BREAKING**: la matrícula deja de capturarse; siempre es el id del estudiante. Se elimina `enrollment_number` del formulario y de los cuerpos de alta/edición de la API. La columna se conserva por compatibilidad (tareas y entregas la leen) y se rellena con el id, incluyendo los registros existentes.
- Los estudiantes existentes se conservan activos y sin inscripción tras la migración.
- Frontend: el modal de estudiante **no** gestiona inscripciones; la tabla de Estudiantes solo muestra la inscripción vigente (columna de solo lectura).
- Dos opciones nuevas en el menú del administrador, cada una con su pantalla de selección múltiple (casillas, un ciclo y un grupo destino):
  - **Inscripciones** (`/inscripciones`): inscribe únicamente a **aspirantes** (estudiante activo que nunca ha tenido inscripción en ningún ciclo).
  - **Reinscripciones** (`/reinscripciones`): toma estudiantes con al menos una inscripción previa y todas sus materias finalizadas, y los envía a un ciclo activo distinto al de su inscripción anterior. La inscripción previa se conserva como historial.
- Antes de inscribir o reinscribir se verifica que ningún estudiante seleccionado esté ya inscrito en el ciclo destino.
- Endpoints nuevos: `GET /inscriptions/aspirants`, `GET /inscriptions/reenrollable` y `POST /inscriptions/bulk` (atómico).
- Materias: en la lista, el selector de estudiante por fila se reemplaza por un botón **Agregar estudiante** que abre un modal con los estudiantes inscritos en el ciclo de la materia (debe estar activo); se eligen con casillas y "Agregar seleccionados" los registra como inscripción individual a la materia. Endpoint nuevo `POST /subjects/{id}/students/bulk` (atómico).
- Grupos: se quita de la lista el selector de estudiante y el botón de inscribir; la inscripción a grupos solo ocurre en Inscripciones y Reinscripciones.
- Catálogo de Bruno actualizado con los nuevos endpoints.

## Capabilities

### New Capabilities
- `cycle-enrollments`: inscripción de estudiantes por ciclo y grupo (alta, cambio de grupo, baja), aspirantes y reinscribibles, alta masiva atómica con verificación de ciclo destino, estado de inscripción en el ciclo activo y matrícula igual al id del estudiante.

### Modified Capabilities

(Ninguna: los specs principales aún no existen en `openspec/specs/`; los requisitos de `groups-enrollment` y `user-management` del cambio `aulatools-tareas` se extienden desde este nuevo capability.)

## Impact

- **Backend (Yii3)**: migración nueva (`inscriptions`, rellenado de `enrollment_number` con el id), módulo `Inscriptions` (controlador, servicio, repositorio) y ajustes en `Users` (alta/edición sin matrícula, listado con inscripción vigente) y `Enrollments` (reuso de la inscripción a grupo y su baja).
- **API**: `GET/POST /inscriptions`, `PUT/DELETE /inscriptions/{id}`, `GET /inscriptions/aspirants`, `GET /inscriptions/reenrollable`, `POST /inscriptions/bulk`, `POST /subjects/{id}/students/bulk`; `GET /users/students` incorpora `enrollment`. Respuestas sin envoltorio `data`, errores con código HTTP y `{"error":{"code","message"}}`; solo administrador.
- **Frontend (Angular 22, zoneless, signals)**: `admin-catalog` (tabla de estudiantes, sin acciones de inscripción), dos pantallas nuevas (Inscripciones y Reinscripciones) con rutas y menú, un servicio de inscripciones, el modal "Agregar estudiante" en Materias y la limpieza de acciones de inscripción en Grupos.
- **Bruno**: carpeta `Inscriptions` (incluye los tres endpoints nuevos) y actualización de las requests de estudiantes.
- **Datos**: se reescribe `students.enrollment_number` con el id; los estudiantes actuales quedan sin inscripción.
