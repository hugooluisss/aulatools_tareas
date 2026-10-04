# Design

## Context

La lista ya consume `/me/tasks` paginado y `/tasks/statuses`; el detalle `/me/tasks/{delivery_id}` solo entrega campos de la tarea, profesor y entrega. Los comentarios están paginados y se leen/escriben en `CommentService`, hoy presentados como modal. El repositorio usa transacciones en algunas acciones, pero las transiciones de entrega y la creación de comentarios actualmente no las envuelven. Las migraciones muestran que solo `task_comments.created_at` existe; las tablas `tasks` y `task_deliveries` no guardan fechas de creación/actualización. El hook de inscripción crea entregas en lote y admite `INSERT IGNORE`/`ON DUPLICATE KEY`.

## Goals / Non-Goals

**Goals:**
- Definir una única tabla de eventos por entrega con actor y payload legible para auditoría.
- Mantener el historial coherente con cambios de negocio mediante transacciones.
- Reutilizar la API de comentarios en una presentación embebible.
- Habilitar lectura futura por docentes y administración sin construirles una vista.

**Non-Goals:**
- Interfaz de bitácora para docentes o administradores.
- Reconstrucción completa de transiciones pasadas que no tienen marca temporal ni actor almacenados.
- Paginación del historial: la respuesta será una lista completa por entrega, que se espera acotada.

## Decisions

- Crear `task_delivery_events` con `delivery_id`, `task_id`, `type`, `actor_id` nullable, snapshot `actor_name`/`actor_role`, `created_at` UTC y `payload` JSON; indexar por `(delivery_id, created_at, id)` y relacionar entrega/tarea/actor con claves foráneas apropiadas. Actor puede ser nulo solo para backfill sin atribución fiable.
- Centralizar inserción/formateo de eventos en un repositorio pequeño reutilizado por `TaskService`, `CommentService` y `TaskDeliveryService`; cada servicio abre una transacción para mutación y evento. `task_created` se emite para cada nueva entrega desde creación de tarea y hooks de inscripción; detectar inserciones efectivas para evitar duplicados en operaciones idempotentes.
- Registrar `task_updated` con cambios relevantes; en cancelación, guardar cambio del estado de la tarea y un `status_changed` por cada entrega que cambie a cancelada. Para entrega/reversión/calificación, guardar estado/nota anterior y nuevo; una nota previa no nula produce `regrade` además de reflejar transición de estado cuando aplique. Comentario y evento `comment_added` con id se insertan en la misma transacción.
- El backfill usa eventos `task_created` sin actor para asignaciones existentes, `comment_added` usando `task_comments.created_at` y autor existente, `delivered` cuando `delivered_at` exista y `graded` cuando haya nota. No simula `task_updated` ni estados anteriores que no pueden determinarse. Las fechas del panel se derivan del evento `task_created` para entrega y del último evento pertinente; si falta dato histórico, API devuelve `null` y UI muestra «No disponible».
- `GET /deliveries/{id}/history` retorna una lista completa con `actor` e `payload`, ordenada `created_at DESC, id DESC`; la validación reutiliza alcance escolar/propietario/profesor de los comentarios y verifica admin de escuela. La política de privacidad responde 404 a estudiantes ajenos, consistente con el detalle.
- La tarjeta será un `<a routerLink>` que contiene toda la presentación no interactiva. Para el panel, `DeliveryCommentsComponent` aceptará una variante embebida, conservando el modo modal usado por otros consumidores si los hubiera; Escape/cierre solo opera en modo modal.
- SCSS de tarjetas usa BEM, CSS Grid con `repeat(auto-fill, minmax(...))` y variables SCSS existentes; no se añade dependencia.

## Risks / Trade-offs

- [La cantidad de eventos por entrega puede crecer] → historial sin paginación para mantener API/UI simples inicialmente; índice compuesto y posibilidad de paginar después.
- [Los datos anteriores no permiten fechas completas ni responsables] → backfill marca actor desconocido cuando corresponda y la UI no presenta valores inferidos como hechos.
- [Asignación masiva puede crear muchos eventos] → inserción por lote ligada a las filas nuevas y a la transacción del hook.
- [Nombres de actor cambian después] → guardar snapshot de nombre/rol junto con id para que la bitácora conserve el texto mostrado en el momento del evento.

## Migration Plan

1. Crear tabla e índices de eventos y poblar el backfill mínimo de entregas, comentarios, entregas enviadas y calificaciones existentes.
2. Desplegar backend que registre cada acción de forma atómica y exponga la lectura autorizada; las filas nuevas empiezan a acumular historial completo desde ese punto.
3. Actualizar documentación/Bruno y luego el frontend de tarjetas y panel.
4. Rollback: revertir código antes de quitar la tabla; al retirar la migración se pierde la bitácora generada y no afecta las tablas funcionales originales.

## Open Questions
