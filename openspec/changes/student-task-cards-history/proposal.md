# Proposal

## Why

La lista de «Mis tareas» presenta información tabular que no se adapta bien a pantallas pequeñas y separa el acceso al detalle de cada entrega. Además, el detalle no reúne el contexto, los comentarios y los cambios de estado/calificación en una vista trazable para el estudiante.

## What Changes

- **BREAKING** Reemplazar la tabla de «Mis tareas» por tarjetas enlazables accesibles, con diseño adaptable, filtros, ciclo, búsqueda, paginación y estado vacío conservados.
- Convertir `/my-tasks/:deliveryId` en un panel con datos completos de la tarea, comentarios integrados y una bitácora cronológica.
- Registrar eventos persistentes por entrega para asignación, edición/cancelación de tarea, comentarios, entrega/reversión, calificación/recalificación y cambios de estado.
- Añadir lectura autorizada de la bitácora para estudiante propietario, profesor de la materia y administrador.
- Actualizar pruebas de servicios, colección Bruno y `docs/api.md`.

## Capabilities

### New Capabilities
- `student-task-cards-history`: Consulta de tareas del estudiante en tarjetas y panel con conversación y bitácora auditable por entrega.

### Modified Capabilities

## Impact

- Frontend: `features/tasks/my-tasks`, `task-detail`, `delivery-comments`, servicios de tareas y componentes compartidos de estados, paginación y breadcrumbs.
- Backend: `Tasks` (servicio/repositorio/controlador y hook de inscripción), `TaskComments`, migración para eventos y endpoint de lectura.
- Persistencia/API: tabla de eventos por entrega y `GET /deliveries/{id}/history`; documentación y solicitudes Bruno.
