# Tasks

## 1. Backend: modelo y eventos

- [x] 1.1 Crear migración reversible de `task_delivery_events`, índices y backfill mínimo documentado; verificar esquema, backfill y rollback con pruebas de migración.
- [x] 1.2 Implementar repositorio/servicio de eventos con snapshot de actor y payload JSON; verificar serialización UTC, orden determinista y actor de sistema.
- [x] 1.3 Registrar `task_created` al crear tareas y al generar entregas por inscripción/grupo dentro de la transacción; probar creación normal y que operaciones idempotentes no dupliquen eventos.
- [x] 1.4 Registrar `task_updated`, cancelación y cambios de estado por entrega dentro de transacciones; añadir pruebas de servicio que comprueben tipo, actor, estado anterior/nuevo y rollback ante fallo.
- [x] 1.5 Registrar `delivered`, `undelivered`, `graded` y `regrade` con valores anterior/nuevo de forma atómica; añadir prueba de servicio para cada acción y recalificación.
- [x] 1.6 Registrar `comment_added` con id de comentario en la misma transacción de creación; probar autor/rol, vínculo al comentario y rollback.
- [x] 1.7 Exponer `GET /deliveries/{id}/history` con lista completa nueva primero y autorización del estudiante propietario, profesor asignado y admin; probar respuestas autorizadas y denegación entre escuelas/usuarios.
- [x] 1.8 Completar fechas del panel desde eventos disponibles y documentar `null` cuando no exista fuente histórica; probar formato y caso legacy sin eventos fechados.
- [x] 1.9 Documentar endpoint, tipos/payload y política de fechas/backfill en `docs/api.md`, y agregar solicitud Bruno autenticada para el historial; verificar rutas, ejemplo de respuesta y roles documentados.

## 2. Frontend: lista y panel

- [ ] 2.1 Reemplazar tabla de «Mis tareas» por tarjetas con enlace accesible, estado de API, vencimiento, calificación y SCSS BEM con variables y Grid autoajustable; verificar varias columnas en escritorio y columna única en móvil.
- [ ] 2.2 Mantener búsqueda, estados, ciclo, paginación bajo la cuadrícula y estado vacío; verificar que cada cambio conserva consultas paginadas y reinicia a página uno al filtrar.
- [ ] 2.3 Convertir el detalle en panel con breadcrumbs, campos completos, estado y fechas legibles; verificar visualmente ruta propia y valores faltantes.
- [ ] 2.4 Hacer `DeliveryCommentsComponent` embebible y reutilizarlo en el panel sin modal, manteniendo carga, lectura, paginación y envío; verificar hilo vacío, comentario enviado y errores.
- [ ] 2.5 Añadir cliente/tipos de historial y timeline accesible con mensajes en español, actor, fecha/hora, cambios de estado y notas; verificar orden descendente y eventos con payload incompleto.
- [ ] 2.6 Verificar integración de navegación desde tarjetas al panel y regreso por breadcrumb en escritorio y móvil.

## 3. Verificación integral

- [ ] 3.1 Ejecutar pruebas de servicios backend para cada evento y las pruebas frontend de lista, detalle, comentarios e historial; corregir fallos relacionados con el cambio.
- [ ] 3.2 Revisar que `docs/api.md`, colección Bruno, contrato de respuesta y reglas de autorización coincidan; verificar la colección Bruno y la documentación final.
