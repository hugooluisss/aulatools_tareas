# Proposal

## Why

Los reportes existentes son exclusivos del administrador, aunque el docente necesita consultar e imprimir asistencia de sus propios grupos y revisar en papel el avance de tareas de sus estudiantes. Ambos reportes también necesitan una arquitectura que permita cambiar o agregar formatos de salida sin acoplar los servicios de datos a la generación del documento.

## What Changes

- Ampliar «Lista de asistencia» para docentes: se conserva el endpoint y la pantalla, el docente ve solo asignaturas y grupos que enseña, y la API restringe también el acceso a esos recursos; el administrador conserva el acceso completo.
- Mostrar «Reportes» en el menú lateral de docentes y administradores, con la lista de asistencia y la nueva boleta de tareas.
- Añadir «Boleta de tareas» en PDF: seleccionar asignatura y estudiantes inscritos, permitir seleccionar todos, exigir al menos uno e imprimir una página por estudiante con las tareas, fechas y calificaciones.
- Excluir tareas canceladas de la boleta.
- Construir ambos reportes mediante modelos de vista de datos puros y un renderizador intercambiable; el renderizador PDF con Dompdf será el formato inicial y conservará el resultado actual de asistencia.

## Capabilities

### New Capabilities
- `task-report-card`: selección de estudiantes y generación de boletas PDF de tareas por asignatura.

### Modified Capabilities
- `attendance-report`: habilitar el reporte para docentes con alcance limitado a sus asignaturas y grupos, conservando el acceso completo del administrador.

## Impact

- Backend: módulo `Reports`, autorización y consultas de asistencia, nuevos modelos de vista y abstracción de renderizado, implementación PDF, binding DI, nuevo endpoint de boletas y pruebas.
- Frontend: rutas y vistas para ambos roles, opciones de grupos/asignaturas/estudiantes acotadas y descarga PDF con estado ocupado.
- Menú del shell y requests de Bruno para el nuevo endpoint.
