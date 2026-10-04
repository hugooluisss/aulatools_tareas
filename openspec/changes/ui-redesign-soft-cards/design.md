# Design

## Context

La app ya usa Angular standalone, Bootstrap y Bootstrap Icons. Los tokens de Bootstrap se configuran en `styles.scss` desde `styles/_variables.scss`; el body y `.card` tienen reglas globales. El shell es una barra lateral con grupos desplegables de administración y enlaces por rol, mientras que la vista móvil hoy oculta la barra lateral tras un botón. «Mis tareas» ya está migrando a tarjetas mediante `student-task-cards-history`, por lo que esta propuesta solo define su capa visual y consume el comportamiento acordado allí.

## Goals / Non-Goals

**Goals:**
- Concentrar la identidad visual en tokens SCSS y propiedades CSS y conectarla con variables Bootstrap.
- Mantener navegación por rol y rutas, dando un diseño de escritorio y móvil con accesibilidad por teclado.
- Dar un estilo compartido a superficies y aplicarlo a las vistas de tareas, materias y calendario.

**Non-Goals:**
- Agregar flujos de producto, cambiar endpoints o datos, reemplazar Bootstrap o implementar modo oscuro.
- Alterar la estructura funcional de tareas cubierta por `student-task-cards-history`.

## Decisions

- **Tokens como fuente única:** ampliar `_variables.scss` con colores semánticos, escala pastel y textos emparejados, superficies, radios, sombras, tipografía y escala de espacios; exponerlos como custom properties en `:root`. Pasar a Bootstrap sus variables de color, borde y tipografía de forma consistente. Esto permite restilar componentes sin repetir valores; se evita crear un sistema de temas paralelo.
- **Tipografía progresiva:** cargar Inter desde Google Fonts en `index.html`, con `system-ui, sans-serif` como fallback. No se agrega paquete de fuentes al frontend.
- **Shell conserva su modelo de rutas:** mantener los enlaces filtrados por rol y los grupos contraíbles existentes; añadir metadatos visuales de icono a destinos y estructurar el modo móvil como una barra inferior con hasta cinco enlaces principales y entrada «Más» para destinos secundarios. El encabezado superior usa el contexto de ruta y menú de usuario existente. No se introducen rutas nuevas.
- **Estilos compartidos primero:** actualizar estilos base/selectores de Bootstrap y los componentes reutilizados; después aplicar ajustes locales al panel/tarjetas de tareas, materias y calendario. Los colores de estado del servidor siguen siendo la fuente de datos y no se reemplazan por una paleta fija.
- **Calendario dentro del contrato actual:** conservar el modelo mensual, controles y panel de selección existentes. Adaptar la cuadrícula y los chips; en móvil, la presentación compacta semanal puede derivarse del mismo rango de días y selección, sin cambios de API.
- **Convivencia con cambio en curso:** integrar visualmente las tarjetas y panel de `student-task-cards-history` cuando estén disponibles; no duplicar ni editar su propuesta/especificación, y coordinar cualquier modificación futura de esos mismos componentes en la implementación.

## Risks / Trade-offs

- [Los colores pastel pueden fallar contraste si se combina texto incorrecto] → Definir pares de superficie/texto validados con contraste AA y conservar señales textuales para estados y selección.
- [La barra inferior puede ocultar contenido o controles en móvil] → Reservar espacio inferior seguro, validar scroll y revisar pantallas estrechas con teclado/lector de pantalla.
- [Google Fonts puede no estar disponible] → Mantener fallback de sistema sin bloquear carga ni legibilidad.
- [Cambios globales de Bootstrap pueden alterar pantallas fuera de muestra] → Aplicar tokens semánticos y revisar las pantallas relacionadas con una comprobación visual de regresión.

## Migration Plan

1. Añadir y conectar los tokens globales y fuente; verificar superficies base.
2. Rediseñar shell para escritorio y móvil, conservando rutas por rol.
3. Aplicar componentes compartidos y después las vistas de tareas, materias y calendario.
4. Ejecutar las especificaciones relacionadas y hacer revisión visual en escritorio y móvil. El rollback consiste en revertir los estilos/markup de presentación de este cambio; no hay migración de datos.
