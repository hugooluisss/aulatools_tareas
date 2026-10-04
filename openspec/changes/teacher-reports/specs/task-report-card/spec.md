# Spec Delta

## Purpose

Permite al administrador o docente generar boletas imprimibles de tareas por asignatura para estudiantes seleccionados, usando sus entregas y calificaciones registradas.

## ADDED Requirements

### Requirement: Task report card PDF
El administrador y el docente SHALL poder seleccionar una asignatura y uno o más estudiantes inscritos en ella y descargar un PDF con una boleta por estudiante, con salto de página entre boletas. Cada boleta SHALL incluir escuela, ciclo, asignatura, docente, nombre y matrícula del estudiante, y una tabla de tareas con Tarea, Fecha de entrega, Fecha entregada y Calificación. Las tareas canceladas SHALL excluirse. Las fechas SHALL mostrarse solo como fecha, sin hora; las fechas o calificaciones ausentes SHALL mostrarse como «—».

#### Scenario: Generate cards for selected students
- **WHEN** un usuario autorizado selecciona una asignatura y varios estudiantes inscritos y genera el reporte
- **THEN** recibe un PDF con una boleta por estudiante seleccionado y un salto de página entre estudiantes

#### Scenario: Empty values and date-only display
- **WHEN** una tarea no tiene entrega o calificación, o su entrega tiene hora
- **THEN** la boleta muestra «—» para el valor ausente y la fecha entregada sin componente de hora

#### Scenario: Cancelled tasks excluded
- **WHEN** la asignatura tiene tareas activas y canceladas
- **THEN** las boletas muestran las tareas activas y omiten las canceladas

### Requirement: Task report card selection and access
La pantalla SHALL permitir elegir asignatura y ciclo cuando sea necesario, mostrar los estudiantes inscritos en esa asignatura con casillas de selección, ofrecer «Seleccionar todos» y exigir al menos un estudiante para generar el PDF. El endpoint SHALL aceptar `subject_id` y la lista de `student_ids` mediante POST. El backend SHALL verificar el rol, el ámbito escolar, que el docente enseña la asignatura solicitada y que cada estudiante seleccionado está inscrito en ella; ante asignatura ajena responderá 403 o 404 y ante estudiantes no inscritos responderá 400 o 404 sin generar un PDF.

#### Scenario: Select all and require one student
- **WHEN** el usuario abre la lista de estudiantes de una asignatura
- **THEN** puede seleccionar todos, y no puede generar el PDF con cero estudiantes seleccionados

#### Scenario: Teacher requests own subject
- **WHEN** un docente selecciona una asignatura que enseña y estudiantes inscritos en ella
- **THEN** la API devuelve el PDF solicitado

#### Scenario: Teacher requests another subject
- **WHEN** un docente envía el identificador de una asignatura que no enseña
- **THEN** la API responde 403 o 404 sin revelar datos ni generar PDF

#### Scenario: Student not enrolled in subject
- **WHEN** se incluye un estudiante que no está vinculado a la asignatura
- **THEN** la API responde 400 o 404 y no genera el reporte

### Requirement: Task report card output rendering is replaceable
El sistema SHALL construir los datos de boleta en una representación de vista independiente del formato de salida. Un renderizador intercambiable SHALL convertir esa representación a una respuesta descargable con contenido, tipo de contenido y nombre de archivo; la salida inicial SHALL ser PDF.

#### Scenario: Task cards rendered as PDF
- **WHEN** un usuario autorizado genera boletas de tareas
- **THEN** la representación de vista se convierte en el PDF solicitado

#### Scenario: Alternate renderer consumes task cards
- **WHEN** un renderizador alternativo recibe las boletas
- **THEN** puede consumir sus encabezados, tablas y secciones por estudiante sin depender de HTML ni Dompdf
