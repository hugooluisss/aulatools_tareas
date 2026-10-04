# Tasks

## 1. Backend

- [x] 1.1 Crear migración reversible para `delivery_comment_reads` con unicidad por usuario/entrega y claves foráneas; verificar con la migración y su reversión.
- [x] 1.2 Añadir operación autenticada `POST /deliveries/{id}/comments/read` con autorización de docente asignado/admin y cursor al comentario de estudiante más reciente; verificar permisos y cursores con pruebas de servicio/repositorio.
- [x] 1.3 Exponer conteo e indicador de comentarios de estudiante sin leer en filas de entregas, y conteo de entregas nuevas por tarea en la lista de tareas de materia; verificar valores y envolturas paginadas con pruebas.
- [x] 1.4 Añadir pruebas backend para comentario nuevo, comentario posterior al cursor, aislamiento entre usuarios, comentario propio excluido y entrega sin comentarios; verificar también las respuestas/error de la operación.
- [x] 1.5 Actualizar solicitudes y documentación Bruno para marcar comentarios leídos; verificar el endpoint, autorización y confirmación documentados.

## 2. Frontend

- [x] 2.1 Crear `app-breadcrumbs` standalone con navegación `aria-label="Migas de pan"`, lista ordenada y `aria-current` en el último elemento; verificar semántica y navegación por teclado en pruebas.
- [x] 2.2 Agregar migajas a materias, detalle de materia, entregas y detalle de tarea estudiantil con nombres reales y destinos docentes/admin/alumno; verificar enlaces y texto por rol en pruebas.
- [x] 2.3 Convertir comentarios en modal accesible reutilizable, abrirlo desde fila de entrega y detalle estudiantil, invocar marcar leído al abrir y refrescar el indicador al cerrar; retirar `deliveries/:deliveryId/comments`; verificar que no se navega a ruta de comentarios y que el flujo modal funciona.
- [x] 2.4 Mostrar «Nuevo» y conteo de comentarios no leídos en la lista de entregas, y conteo de entregas nuevas en tareas de materia; verificar estados visibles según los campos API.
- [x] 2.5 Sustituir `prompt()` de calificación por modal con entrada numérica 0–100, validación y botón ocupado; mantener la acción solo para docentes en entregas Entregadas y verificar guardado, errores y bloqueo de envíos duplicados.
- [x] 2.6 Añadir/actualizar pruebas de componentes para accesibilidad de migajas y modales, apertura/cierre, lectura al abrir, visibilidad por rol y validación/estado ocupado de calificación; verificar la suite frontend relacionada.
