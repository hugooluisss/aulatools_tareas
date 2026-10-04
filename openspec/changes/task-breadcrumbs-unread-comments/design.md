# Design

## Context

Las rutas de comentarios reciben solo el identificador de entrega; las páginas anteriores consultan datos paginados y algunas vistas no conservan el contexto de materia/tarea. El panel actual de comentarios está montado en una ruta propia, mientras que los diálogos existentes usan componentes standalone. Las migraciones son reversibles. Véase `proposal.md` y las especificaciones para el comportamiento esperado.

## Goals / Non-Goals

**Goals:**
- Mantener el estado de lectura independiente para cada usuario y entrega.
- Exponer conteos utilizables por las listas existentes sin cambiar sus envolturas paginadas.
- Cargar los nombres necesarios para las migajas desde endpoints existentes o una consulta de contexto mínima.
- Mantener comentarios y calificación sobre la página actual en diálogos pequeños accesibles.

**Non-Goals:**
- Agregar una cuenta total de comentarios nuevos al menú lateral.
- Cambiar la visibilidad de los hilos ni la experiencia de comentarios del estudiante.
- Agregar migajas a la vista general administrativa de tareas, que no forma parte del flujo de páginas solicitado.

## Decisions

- **Lectura explícita por POST al abrir el diálogo.** Agregar una operación autenticada `POST /deliveries/{id}/comments/read`. El servicio valida el mismo acceso que el listado, restringe la mutación a docente de la materia o administrativo y guarda el ID máximo de comentario de estudiante existente. El GET paginado no produce efectos secundarios. El modal invoca la operación al abrirse y carga los comentarios; comentarios de estudiante posteriores al ID guardado siguen nuevos. La ruta frontend `deliveries/:deliveryId/comments` se elimina.
- **Punto de lectura como ID de comentario.** La tabla `delivery_comment_reads` tendrá clave única `(user_id, delivery_id)` y `last_read_comment_id` nullable, con claves foráneas e índices adecuados. El conteo pendiente es el de comentarios de rol estudiante cuyo ID supera el punto guardado y cuyo autor no es el usuario actual; si no hay fila, se cuentan todos los comentarios de estudiante.
- **Conteos con el usuario actual en las consultas de lista.** La lista de entregas añade `unread_student_comment_count` y `has_unread_student_comments`; el listado de tareas por materia añade `unread_deliveries_count`. Los conteos se calculan en la consulta paginada y se normalizan en los objetos existentes para conservar el sobre `items/page/per_page/total/total_pages`.
- **Migajas declaradas por vista.** Un componente standalone pequeño recibe elementos `{label, url?}`. Cada página declara sus elementos según rol y datos cargados; el último es texto actual. La navegación visible termina en entregas: el diálogo de comentarios no añade elemento ni ruta. La pantalla de entregas y detalle de materia usan contexto mínimo de materia/tarea por IDs, con rutas de retorno específicas para docente y admin (`/my-subjects` y `/admin/tasks`). El estudiante enlaza a `/my-tasks`; al abrir comentarios mantiene detalle subyacente y migajas.
- **Diálogos de comentarios y calificación.** Convertir el panel de comentarios en componente modal reutilizable invocado desde entregas y detalle estudiantil, siguiendo el patrón accesible de notas de estudiante. Reemplazar `prompt()` por un diálogo de calificación con entrada numérica `min=0`, `max=100`, validación en cliente, y botón ocupado durante el PUT existente. Mostrar la acción solo a docentes y solo en entregas Entregadas; conservar la validación backend existente.
- **Pruebas alineadas con patrones existentes.** Añadir cobertura de servicio/repositorio/controlador y componentes Angular para permisos, conteos, accesibilidad, apertura/cierre de diálogos, formulario de calificación y ausencia de navegación a una ruta de comentarios; documentar marcar lectura y comentarios en modal en Bruno.

## Risks / Trade-offs

- [ID de lectura presupone orden creciente de IDs] → Los comentarios usan ID autoincremental y consultas ordenadas por fecha e ID; usar el ID como cursor evita ambigüedad entre comentarios con igual fecha.
- [La pantalla puede tardar si marca leído y lista comentarios en paralelo] → Encadenar la confirmación de lectura y la carga inicial al abrir el diálogo; al cerrarlo, refrescar el indicador en la lista que permanece debajo.
- [Diálogos sobrecargados en pantallas pequeñas] → Mantener el contenido con scroll interno, etiquetas y controles accesibles, reutilizando el patrón visual de StudentNotesModal.
- [El detalle de materia actualmente no expone el nombre de materia] → Ampliar el DTO existente con el nombre necesario o consultar el recurso de materia ya disponible, sin duplicar una carga innecesaria.

## Migration Plan

1. Crear tabla reversible de lecturas y desplegar la migración antes de publicar el código que la consulta; filas inexistentes se interpretan como nunca leídas.
2. Publicar la operación backend y el cliente UI con diálogo modal; retirar la ruta frontend anterior. No se requiere backfill: los comentarios existentes cuentan como nuevos para usuarios sin cursor hasta que abran el diálogo.
3. Para revertir, retirar primero el uso de los nuevos campos/operación y luego revertir la migración, que elimina únicamente el estado de lectura.
