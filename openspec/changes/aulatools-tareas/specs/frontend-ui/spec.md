# Spec Delta

## Purpose

Fija la experiencia visual y las convenciones de interfaz de la aplicación Angular.

## ADDED Requirements

### Requirement: Layout and visual style
La interfaz SHALL usar barra lateral morada con la opción activa resaltada, fondo lavanda, tarjetas blancas de esquinas redondeadas y tarjetas de indicadores con borde de color, en español, con un menú acorde al rol del usuario.

#### Scenario: Role menu
- **WHEN** inicia sesión un estudiante
- **THEN** el menú solo muestra Inicio, Mis tareas, Materias, Calendario y Avisos

#### Scenario: Admin menu
- **WHEN** inicia sesión un administrativo
- **THEN** el menú incluye además Estudiantes, Profesores, Ciclos, Grupos y Materias

### Requirement: Icon actions with tooltip
Las acciones de fila en listas (editar, eliminar, ver detalle, marcar entregada, calificar) SHALL presentarse como botones de solo ícono con tooltip y etiqueta accesible con el nombre de la acción.

#### Scenario: Hover action
- **WHEN** el usuario pasa el cursor o enfoca un botón de ícono
- **THEN** se muestra un tooltip con la acción

### Requirement: Responsive and accessible
La interfaz SHALL ser utilizable en pantallas móviles y permitir operar los controles con teclado.

#### Scenario: Mobile width
- **WHEN** el ancho es menor a 768 px
- **THEN** la barra lateral se colapsa a un menú desplegable
