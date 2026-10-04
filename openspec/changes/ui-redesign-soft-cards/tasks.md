# Tasks

## 1. Tokens y fundamentos visuales

- [x] 1.1 Definir paleta, pares pastel/texto AA, tipografía, radios, sombras y escala de espaciado en SCSS y exponer custom properties; verificar especificaciones frontend relacionadas y revisar visualmente body/cards en escritorio y móvil.
- [x] 1.2 Cargar Inter con fallback `system-ui` y sincronizar variables de Bootstrap con los tokens; verificar compilación/especificaciones frontend relacionadas y confirmar visualmente formularios y controles base.

## 2. Shell y componentes compartidos

- [x] 2.1 Restilar shell de escritorio con sidebar marina, grupos, iconos, píldora activa, encabezado escolar, barra superior y cuenta, preservando destinos por rol; verificar specs del shell y revisar visualmente navegación y foco por teclado.
- [x] 2.2 Implementar navegación inferior móvil de hasta cinco destinos más «Más» y barra superior compacta; verificar specs del shell y revisar visualmente anchos móviles, foco y que no se oculte contenido.
- [x] 2.3 Aplicar tokens a botones, inputs, badges, tarjetas, tabla, modales, breadcrumbs y paginador, conservando estados obtenidos de `GET /tasks/statuses`; verificar specs de componentes compartidos y hacer revisión visual de estados, hover y foco.

## 3. Vistas principales

- [x] 3.1 Aplicar tarjetas pastel y metadatos claros a «Mis tareas» y su panel sobre `student-task-cards-history`, sin cambiar su comportamiento; verificar las specs frontend de tareas y hacer revisión visual de escritorio/móvil.
- [x] 3.2 Restilar tarjetas de materias del profesor con los componentes compartidos; verificar specs frontend de materias y hacer revisión visual de acciones, estados vacíos y móvil.
- [x] 3.3 Aplicar el sistema al calendario, chips de eventos/tareas y panel de día, con disposición compacta en móvil; verificar specs frontend de calendario y hacer revisión visual del mes, selección, detalle y móvil.

## 4. Integración

- [x] 4.1 Ejecutar las specs frontend relacionadas con shell, componentes compartidos, tareas, materias y calendario; revisar visualmente una ruta representativa por rol en escritorio y móvil y corregir regresiones de espaciado, contraste o desbordamiento.
