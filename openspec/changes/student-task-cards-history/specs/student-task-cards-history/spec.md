# Spec Delta

## Purpose

La capacidad ofrece al estudiante una navegación adaptable entre sus entregas y un panel que concentra los datos, comentarios y un registro cronológico verificable de los cambios.

## ADDED Requirements

### Requirement: Lista de tareas del estudiante en tarjetas

El sistema SHALL mostrar «Mis tareas» como tarjetas adaptables en lugar de una tabla. En escritorio SHALL distribuir varias tarjetas por fila con una cuadrícula autoajustable y en móvil SHALL mostrar una columna. Cada tarjeta SHALL mostrar nombre, materia en negritas mediante la clase BEM `task-card__subject`, vencimiento, marca «Vencida» cuando corresponda, estado como etiqueta de texto simple con un punto cuyo color proviene de `GET /tasks/statuses`, y calificación cuando exista. SHALL mostrar el estado sin formato de botón o píldora y no SHALL mostrar una etiqueta «Ver tarea», pues la tarjeta completa SHALL ser un enlace real y accesible por teclado a `/my-tasks/:deliveryId`. SHALL conservar búsqueda, filtro de estado, selector de ciclo, paginación bajo la cuadrícula y estado vacío.

#### Scenario: Navegación adaptable a una entrega

- **WHEN** un estudiante abre «Mis tareas» en escritorio o móvil
- **THEN** ve tarjetas en varias columnas o una sola columna respectivamente, y activar una tarjeta por teclado o puntero abre su entrega

#### Scenario: Filtros y resultados vacíos

- **WHEN** el estudiante busca, filtra por estado o ciclo y cambia de página
- **THEN** la lista mantiene esos controles y coloca el paginador debajo de las tarjetas; si no hay resultados informa el estado vacío

### Requirement: Panel de detalle de entrega

La ruta `/my-tasks/:deliveryId` SHALL presentar breadcrumbs «Mis tareas > tarea», nombre, descripción, materia, profesor, fecha límite, estado, calificación, fecha de entrega y fechas de creación y última actualización. SHALL mostrar los comentarios y su formulario dentro del panel, reutilizando el flujo actual sin modal, y una bitácora accesible de eventos ordenados del más nuevo al más antiguo.

#### Scenario: Ver panel completo

- **WHEN** el estudiante abre una entrega propia
- **THEN** el panel muestra los datos disponibles, la conversación integrada y la bitácora en una lista accesible

#### Scenario: Fechas históricas de datos existentes

- **WHEN** se muestra una tarea o entrega anterior a la bitácora
- **THEN** las fechas de creación/actualización no inventan precisión que el esquema anterior no almacenaba y se presentan como desconocidas cuando no puedan derivarse

### Requirement: Persistencia de eventos por entrega

El sistema SHALL persistir cada evento de la bitácora con entrega, tarea, tipo, actor, rol/nombre del actor, fecha UTC y datos JSON pertinentes. SHALL registrar, en la misma transacción que la acción correspondiente: `task_created` al asignarse cada entrega; `task_updated`; `comment_added` con id de comentario; `delivered`; `undelivered`; `graded` con calificación; `regrade` o cambio de calificación con valores anterior/nuevo; y cambios de estado anterior/nuevo, incluida cancelación. Un cambio de calificación repetido SHALL quedar distinguible de la primera calificación. La migración SHALL realizar un backfill mínimo de estados actuales derivables y comentarios existentes; no SHALL atribuir actores ni momentos históricos que no constan en el esquema.

#### Scenario: Acción y evento atómicos

- **WHEN** una acción que genera un evento se confirma
- **THEN** el cambio de negocio y su evento se guardan juntos, con actor, rol y detalles pertinentes

#### Scenario: Fallo al guardar el evento

- **WHEN** no se puede persistir el evento de una acción
- **THEN** la transacción de negocio también se revierte

#### Scenario: Asignación posterior por inscripción

- **WHEN** el hook crea una entrega para una inscripción o grupo
- **THEN** se registra `task_created` por cada nueva entrega, sin duplicar eventos por filas ya existentes

#### Scenario: Migración de datos existentes

- **WHEN** se migra una base con tareas y entregas previas
- **THEN** se crea un historial mínimo usando solamente comentario existente, fecha de entrega, calificación y estado observables, identificando como sistema los datos sin actor atribuible

### Requirement: Consulta autorizada de bitácora

El sistema SHALL ofrecer `GET /deliveries/{id}/history` como lista completa ordenada de más nuevo a más antiguo, incluyendo id, tipo, fecha UTC, actor `{id?,name,role}`, y payload. SHALL autorizar únicamente al estudiante propietario de la entrega, al profesor asignado a su materia y al administrador de la escuela; las demás solicitudes SHALL recibir el error de acceso correspondiente sin revelar entregas de otro estudiante.

#### Scenario: Consulta por participante autorizado

- **WHEN** el estudiante propietario, profesor asignado o administrador consulta la bitácora
- **THEN** recibe todos los eventos de esa entrega, del más nuevo al más antiguo

#### Scenario: Consulta no autorizada

- **WHEN** otro estudiante o profesor ajeno consulta la bitácora
- **THEN** recibe una respuesta de no encontrado o prohibido según la política actual, sin datos de eventos

### Requirement: Texto comprensible de la bitácora

El panel SHALL presentar cada evento con fecha y hora, nombre del actor y texto en español que identifique la acción y los datos relevantes, incluyendo cambios de estado, calificación y comentarios. La lista SHALL ser navegable y comprensible con tecnologías de asistencia.

#### Scenario: Comentario y calificación

- **WHEN** la bitácora contiene comentario o calificación
- **THEN** presenta textos como «Ana Pérez comentó» y «El profesor Jorge calificó: 90» con su fecha y hora

#### Scenario: Cambio de estado o calificación

- **WHEN** la bitácora contiene una transición o recalificación
- **THEN** presenta valores anterior y nuevo en texto español sin depender solo del color
