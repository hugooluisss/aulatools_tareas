# Spec Delta

## Purpose

Establece una presentación visual coherente, adaptable y accesible para navegación, controles, tarjetas y calendario, manteniendo los flujos actuales de tareas y administración escolar.

## ADDED Requirements

### Requirement: Tokens visuales coherentes
La interfaz autenticada SHALL usar una paleta común definida como tokens reutilizables que incluya azul marino para navegación, azul primario, seis colores pastel para tarjetas con colores de texto legibles, neutros, tipografía, radios, sombras y espaciado. Los textos sobre superficies pastel SHALL cumplir contraste AA, SHALL mantener foco visible y SHALL contar con fallback de fuente de sistema. Bootstrap SHALL conservarse y compartir los tokens de tema.

#### Scenario: Presentación legible de tarjetas pastel
- **WHEN** una vista presenta contenido sobre cualquiera de las superficies pastel
- **THEN** el texto asociado mantiene contraste AA, la jerarquía se distingue sin depender del color y los controles enfocables muestran un indicador de foco visible

### Requirement: Shell de navegación adaptable
La aplicación SHALL presentar en escritorio una barra lateral azul marino con nombre de escuela, navegación con iconos y etiquetas, grupos contraíbles cuando aplique, elemento activo como píldora y acciones de cuenta; SHALL presentar una barra superior clara con título y menú de usuario. En móvil SHALL reemplazar la navegación lateral por una barra inferior con un máximo de cinco destinos principales y «Más», y SHALL compactar la barra superior. Los destinos visibles SHALL respetar el rol y rutas existentes.

#### Scenario: Navegación de escritorio
- **WHEN** un usuario autenticado abre una pantalla en escritorio
- **THEN** ve el shell con navegación lateral y superior, reconoce el destino actual y puede operar los grupos contraíbles por teclado

#### Scenario: Navegación móvil
- **WHEN** un usuario autenticado abre una pantalla en móvil
- **THEN** ve una barra inferior compacta con hasta cinco destinos y acceso «Más», y puede identificar y activar el destino actual

### Requirement: Estilo compartido de controles y superficies
Los controles compartidos SHALL usar botones primarios redondeados, entradas consistentes, chips de estado legibles, superficies de tarjeta suaves, tablas ligeras con esquinas redondeadas y realce de fila, modales redondeados y breadcrumbs coherentes. Los valores de color y etiqueta de estados SHALL seguir proviniendo de `GET /tasks/statuses`; el rediseño SHALL conservar su contenido y comportamiento.

#### Scenario: Estados definidos por datos
- **WHEN** se muestra un estado recibido de `GET /tasks/statuses`
- **THEN** conserva su etiqueta y color determinado por los datos, dentro del estilo visual compartido y con texto comprensible

#### Scenario: Foco e interacción compartida
- **WHEN** una persona navega controles compartidos con teclado o puntero
- **THEN** puede reconocer foco, hover y estado activo sin pérdida de contraste ni de las etiquetas accesibles

### Requirement: Presentación de tareas y materias con tarjetas
«Mis tareas», su panel de detalle y las tarjetas de materias del profesor SHALL adoptar las superficies, espaciado y tipografía compartidos, conservando enlaces, datos, acciones, filtros, estados y paginación existentes. Las tarjetas de tarea SHALL poder distinguirse visualmente entre sí mediante variedad pastel legible cuando los datos de presentación permitan asignar ese estilo.

#### Scenario: Tareas y panel del estudiante
- **WHEN** un estudiante abre «Mis tareas» o una entrega
- **THEN** conserva el flujo de tarjetas y panel existente, con jerarquía clara, metadatos secundarios y controles coherentes

#### Scenario: Materias del profesor
- **WHEN** un profesor consulta sus materias
- **THEN** cada materia se presenta como tarjeta compartida legible y mantiene sus acciones actuales

### Requirement: Calendario como vista de muestra
La vista de calendario SHALL usar el sistema visual compartido para encabezado mensual, controles de navegación, cuadrícula, eventos/tareas como chips pastel y panel del día seleccionado. En móvil SHALL mostrar una tira semanal compacta o encabezado semanal equivalente, una lista de una columna y navegación inferior del shell; cualquier acción existente SHALL seguir disponible.

#### Scenario: Selección y detalle del día
- **WHEN** una persona selecciona un día del calendario
- **THEN** la cuadrícula indica el día seleccionado con un énfasis no basado solo en color y el panel muestra los eventos y tareas de ese día

#### Scenario: Calendario móvil
- **WHEN** una persona consulta el calendario en un ancho móvil
- **THEN** la navegación mensual y selección de día se mantienen utilizables, y los elementos se presentan en una columna sin desbordamiento horizontal
