# Proposal

## Why

Las listas paginadas del backend responden con una lista directa y datos de paginación en cabeceras `X-*`; las pantallas solo piden la primera página (20 registros) y no hay forma de ver el resto. Además cada servicio arma su propia estructura de paginación. Se unifica la respuesta paginada y se agrega un paginador y una tabla genérica en el frontend.

## What Changes

- **BREAKING (API)**: toda lista paginada responde con el envoltorio `{ "items": [...], "page", "per_page", "total", "total_pages" }`. Se eliminan las cabeceras `X-Total-Count`, `X-Page` y `X-Per-Page`. Las listas que hoy no paginan siguen siendo una lista directa.
- Un servicio único `Paginator` en el backend valida `page` (mínimo 1) y `per_page` (1 a 100, por defecto 20) y construye la respuesta; todos los servicios paginados lo usan.
- Paginación por **offset** (`page` y `per_page`), no por cursor (ver design.md).
- Frontend: tipo genérico `Page<T>`, componente `PaginatorComponent` y componente `DataTableComponent` que renderiza toda la tabla (encabezados, filas, vacío y paginador) a partir de columnas declaradas como contenido (`<app-column>`), no como atributo.
- Las pantallas con listas migran: estudiantes, profesores, ciclos, grupos, materias (catálogo), avisos, eventos del calendario, tareas por materia y entregas, comentarios y notas del estudiante. Los selectores de formularios piden `per_page=100`.
- Tamaño de página fijo de 20.
- Se actualizan tests, Bruno y `docs/api.md`.

## Capabilities

### New Capabilities
- `paginated-lists`: respuesta paginada unificada y componentes de paginación en el frontend.

### Modified Capabilities

(Ninguna: los specs principales aún no existen; este cambio reemplaza la paginación por cabeceras descrita en el cambio de respuestas sin envoltorio.)

## Impact

- **Backend**: `Shared/Paginator`, `JsonResponse`, servicios y controladores de listas (Groups, Subjects, TaskComments, Calendar, Announcements, Cycles, Users, Tasks, StudentNotes); tests.
- **Bruno y docs**: contratos de las listas paginadas.
- **Frontend**: `Page<T>`, paginador, tabla genérica, servicios de lista y pantallas que los consumen; specs.
