# Proposal

## Why

En el flujo de tareas faltan referencias claras para regresar a la lista anterior, y docentes y administrativos no distinguen las entregas con comentarios nuevos. Esto dificulta ubicar la pantalla actual y dar seguimiento a las respuestas del alumnado.

## What Changes

- Agregar migajas accesibles y reutilizables en las pantallas del flujo de tareas, con nombres reales cuando estén disponibles y rutas de regreso según el rol.
- Abrir comentarios en un modal accesible desde las entregas del docente/administrativo y desde el detalle de tarea del estudiante; retirar la ruta independiente de comentarios. Abrir el modal marca como leídos los comentarios existentes.
- Sustituir el `prompt()` de calificación por un modal pequeño con entrada numérica de 0 a 100 y estado ocupado, conservando que solo el docente puede calificar entregas Entregadas.
- Registrar por usuario y entrega hasta qué comentario de estudiante fue leído; exponer si una entrega tiene comentarios de estudiante sin leer.
- Mostrar el indicador «Nuevo» en entregas y el conteo de entregas con comentarios sin leer en las tareas de la materia.
- Añadir migración, pruebas y ejemplos de API en Bruno para el seguimiento de lectura.

## Capabilities

### New Capabilities
- `task-breadcrumbs`: Migajas accesibles para orientar y recorrer las pantallas de tareas por rol.
- `unread-delivery-comments`: Seguimiento individual de comentarios nuevos de estudiantes y sus indicadores en listados.

### Modified Capabilities

## Impact

- Backend: rutas, servicios y repositorios de `TaskComments` y `Tasks`, migración de base de datos.
- Frontend: páginas de materias, detalle de materia, entregas, Mis tareas y detalle estudiantil; componente compartido de migajas y modales accesibles de comentarios/calificación.
- API: operación explícita invocada al abrir comentarios para marcar como leídos; campos de lectura/no leídos en listas paginadas existentes. Se elimina la ruta frontend independiente de comentarios.
- Bruno: colección de comentarios. Sin dependencias nuevas.
