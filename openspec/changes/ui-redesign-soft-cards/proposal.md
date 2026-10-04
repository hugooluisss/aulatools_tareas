# Proposal

## Why

La interfaz actual usa una base lavanda y navegación sencilla, por lo que las pantallas se sienten «muy básicas» y las listas de trabajo no tienen una jerarquía visual clara. Un lenguaje visual coherente de tarjetas suaves, navegación compacta y mejor adaptación móvil hará que tareas y calendario sean más legibles sin cambiar sus funciones.

## What Changes

- Definir tokens SCSS y propiedades CSS para paleta, seis superficies pastel accesibles, tipografía, radios, sombras y espaciado; integrar las variables de Bootstrap y una fuente moderna con fallback local.
- Rediseñar el shell con barra lateral azul marino, navegación agrupada con icono y etiqueta, píldora activa, encabezado escolar y acciones de cuenta; añadir barra superior clara y navegación inferior móvil de hasta cinco destinos más «Más».
- Unificar el aspecto de controles, estados, tarjetas, tablas, modales y breadcrumbs; conservar los colores de estado recibidos de `GET /tasks/statuses` y mejorar el contraste y el foco visible.
- Aplicar el lenguaje visual a «Mis tareas» y su panel, tarjetas de materias del profesor y calendario, que mostrará eventos/tareas en chips pastel y el detalle del día seleccionado.
- Mantener el alcance frontend y las funciones y contratos existentes; no añadir funciones de producto, cambios backend, modo oscuro ni reemplazo de Bootstrap.

## Capabilities

### New Capabilities
- `soft-card-visual-system`: Sistema visual adaptable y accesible para el shell y las superficies compartidas, con vistas de tareas, materias y calendario como aplicaciones principales.

### Modified Capabilities

## Impact

- Frontend: `styles.scss`, `styles/_variables.scss`, `index.html`, `core/layout/shell`, componentes compartidos de estado, botones, breadcrumbs, paginación y tabla; vistas `features/tasks/my-tasks`, detalle/panel de tarea, materias del profesor y `features/calendar`.
- Se conserva Angular, Bootstrap, Bootstrap Icons y la API actual. La fuente se carga desde Google Fonts con `system-ui` como alternativa.
- La actualización de «Mis tareas» se implementará sobre la capacidad en curso `student-task-cards-history`; sus tarjetas, filtros, paginación y panel permanecen como contrato funcional.
