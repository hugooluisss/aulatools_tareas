# Spec Delta

## Purpose

Consulta global de entregas para el administrador de la escuela.

## ADDED Requirements

### Requirement: Resumen global de entregas

El administrador SHALL poder consultar todas las entregas, con una fila por cada combinación de tarea y estudiante, cuando el estudiante está activo y la materia está activa en su escuela.

#### Scenario: Entregas de estudiantes y materias activos

- **WHEN** el administrador consulta el resumen de tareas
- **THEN** cada fila representa una entrega de un estudiante activo inscrito en la materia activa, y no aparecen entregas de estudiantes o materias inactivos

### Requirement: Búsqueda, filtro y ordenamiento del resumen

El endpoint SHALL aplicar en el servidor una búsqueda única sobre nombre, apellidos, código de materia y nombre de materia, un filtro opcional por varios estados de entrega separados por comas, y ordenar por fecha límite ascendente. `due_at` SHALL usar el formato de fecha `YYYY-MM-DD`.

El filtro SHALL aceptar cero o más estados de entrega separados por comas, cada uno validado contra los estados existentes: `pending`, `delivered`, `graded` y `cancelled`. Un filtro ausente o vacío incluye todos los estados.
Cada fila SHALL incluir título y descripción de la tarea, materia, estudiante, estado de entrega y fecha límite en formato `YYYY-MM-DD`, para que el detalle de solo lectura no requiera otra solicitud.

#### Scenario: Buscar por estudiante o materia

- **WHEN** el administrador envía texto de búsqueda que coincide con nombre/apellidos del estudiante, código o nombre de materia
- **THEN** el resultado incluye las entregas coincidentes y excluye las demás

#### Scenario: Filtrar por varios estados

- **WHEN** el administrador solicita `pending,graded`
- **THEN** se devuelven entregas de ambos estados; cualquier estado no admitido produce el error 400 estándar

#### Scenario: Orden por fecha límite

- **WHEN** hay entregas con distintas fechas límite
- **THEN** se presentan en orden ascendente por fecha límite, aplicando un desempate estable por identificador

### Requirement: Acceso y paginación administrativa

El sistema SHALL exponer `GET /tasks/overview` solo para administradores y limitar los resultados a la escuela del administrador. SHALL aceptar `page` y `per_page` con las reglas existentes, y devolver el envoltorio `{ items, page, per_page, total, total_pages }`.

#### Scenario: Paginación

- **WHEN** el administrador solicita una página y tamaño válidos
- **THEN** la búsqueda y el filtro se aplican antes de paginar y la respuesta usa el envoltorio paginado existente

#### Scenario: Acceso no administrativo

- **WHEN** un usuario que no es administrador solicita el endpoint
- **THEN** la API rechaza el acceso con el formato estándar

### Requirement: Pantalla administrativa de tareas

La entrada «Tareas» del administrador SHALL mostrar el resumen en una tabla genérica, permitir búsqueda y selección múltiple de estados con un botón por estado y `aria-pressed`, y actualizar filas al cambiar página, búsqueda o filtros. La búsqueda SHALL ocupar todo el ancho del área de filtros y usar el placeholder «Buscar estudiante o materia». Ningún estado seleccionado representa todos los estados. Los botones SHALL mostrar etiquetas en español y usar los estilos existentes. SHALL usar las convenciones existentes de botones ocupados durante las solicitudes.

#### Scenario: Seleccionar estados desde la pantalla

- **WHEN** el administrador cambia texto, selecciona o deselecciona un estado, o cambia página
- **THEN** la pantalla solicita los resultados correspondientes, vuelve a la primera página al cambiar los filtros y muestra las filas y paginación recibidas

#### Scenario: Abrir detalle de solo lectura

- **WHEN** el administrador activa una fila con clic o Enter
- **THEN** se abre un diálogo accesible con título, descripción, materia, estudiante, estado de entrega con la etiqueta y colores del catálogo de estados, y fecha límite; Escape y el botón de cerrar cierran el diálogo y no se ofrecen acciones para editar, eliminar, calificar, cancelar ni comentar
