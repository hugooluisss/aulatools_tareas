# Proposal

## Why

El administrador necesita una vista única para revisar las entregas de estudiantes activos en las materias activas de toda la escuela. La vista actual de Tareas permite navegar por materia, pero no ofrece este resumen global ni permite encontrar y filtrar entregas desde el servidor.

## What Changes

- Agregar un resumen administrativo con una fila por entrega (tarea × estudiante), limitado a estudiantes y materias activos.
- Agregar búsqueda por nombre y apellidos del estudiante, código de materia o nombre de materia, y filtro por estado de entrega.
- Búsqueda y filtro en el servidor, ordenamiento por fecha de entrega ascendente y paginación del arreglo completo en el cliente (20 filas por página).
- Catálogo de estados de entrega en base de datos, con etiqueta, color de fondo y color de texto accesibles consultados por API.
- Reutilizar la ruta y entrada del menú administrativo «Tareas», la tabla genérica y las convenciones de botones ocupados.
- Incluir un request Bruno y pruebas del endpoint y la pantalla.

## Capabilities

### New Capabilities

- `all-tasks-overview`: consulta administrativa filtrable de todas las entregas de la escuela, paginada en el cliente.

### Modified Capabilities

(Ninguna.)

## Impact

- Backend: módulo `Tasks`, catálogo de estados y migración, consultas de `Enrollments` para vínculos y estados activos, rutas y pruebas.
- Frontend: página de tareas administrativa, servicio, insignias coloreadas y paginación local mediante `DataTableComponent`, pruebas y ruta/menú existentes.
- Bruno: requests para `GET /tasks/overview` y `GET /tasks/statuses`.
