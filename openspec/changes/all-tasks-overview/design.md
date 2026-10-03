# Design

## Context

La ruta `/admin/tasks` y la entrada «Tareas» ya existen y muestran materias. `TaskRepository` contiene las consultas de entregas y `TaskService` valida sus estados (`pending`, `delivered`, `graded`, `cancelled`). `app-data-table` consume el tipo `Page<T>` y la pantalla adapta el arreglo completo cortándolo localmente en páginas de 20 filas.

## Goals / Non-Goals

**Goals:** mostrar el resumen escolar completo con búsqueda y filtro ejecutados en backend, paginación local y etiquetas/colores de estado cargados desde la base de datos.

**Non-Goals:** reemplazar la navegación de tareas por materia, añadir acciones de gestión de entregas, modificar estados o crear una paginación alternativa.

## Decisions

1. Reemplazar el contenido de la página asociada a `/admin/tasks` por el resumen global, conservando ruta y menú. No añadir una segunda entrada administrativa.
2. Implementar `GET /tasks/overview` con autorización exclusiva de `admin` y alcance `school_id` del usuario actual. Construir todas las filas coincidentes desde tareas, materias, entregas, perfil/usuario del estudiante y vínculos de inscripción; exigir estudiante activo y materia activa. Responder con arreglo JSON plano, sin límite, offset ni envoltorio.
3. Recibir búsqueda opcional `search` y lista opcional `status` separada por comas. Aplicar `search` con coincidencia parcial sobre nombre, apellidos, `subjects.code` y `subjects.name`. Validar cada estado contra los cuatro estados de entrega que ya valida `TaskService`; estado ausente o vacío no filtra y una lista válida se consulta con `IN`.
4. Guardar `tasks.due_at` como `DATE` y exponerlo como `YYYY-MM-DD`. Ordenar por `tasks.due_at ASC` y `task_deliveries.id ASC` para que las fechas iguales tengan orden estable. No ofrecer selector de ordenamiento: el producto exige un único orden.
5. El servicio frontend enviará búsqueda y lista de estados. La pantalla mostrará búsqueda de ancho completo y botones multiselección con etiquetas, color de fondo y color de texto recibidos por `GET /tasks/statuses`; cualquier usuario autenticado puede consultar ese catálogo. `DataTableComponent` recibirá una página `Page<T>` construida al cortar las filas del cliente en grupos de 20; cambiar búsqueda o selección recargará los datos y reiniciará a la primera página. El componente creará un mapa computado por código para reutilizar el catálogo al mostrar el badge.
6. La respuesta expondrá los datos necesarios para la fila: entrega y tarea (ids, título, estado y fecha límite), estudiante (id, nombre y apellidos) y materia (id, código y nombre). El request Bruno será de solo lectura y parametrizable.

## Risks / Trade-offs

- Uniones con inscripciones podrían duplicar filas si un estudiante tiene más de un vínculo equivalente para la materia. La consulta debe preservar una fila por tarea × estudiante, usando `EXISTS` o deduplicación relacional sin alterar el total.
- La búsqueda con coincidencia parcial puede requerir escaneo de filas a gran escala; mantener el alcance de escuela y usar los índices existentes. Agregar índices especializados solo si la carga real lo requiere.

## Migration Plan

Agregar migración de catálogo y endpoints, requests Bruno, integrar la página en la ruta existente y desplegar backend y frontend juntos. El rollback consiste en revertir la migración y los endpoints, y restaurar el componente anterior de `/admin/tasks`.
