# Spec Delta

## Purpose

Navegación del administrador agrupada en el menú Escuela.

## ADDED Requirements

### Requirement: School menu group
El administrador SHALL ver un menú desplegable "Escuela" con Calendario, Avisos, Profesores, Usuarios y Generales, y SHALL NOT ver esas opciones como enlaces sueltos.

#### Scenario: Admin menu
- **WHEN** un administrador abre la aplicación
- **THEN** el menú muestra Control escolar, Catálogos, Reportes, Tareas y Escuela, y Escuela contiene Calendario, Avisos, Profesores, Usuarios y Generales

#### Scenario: Teacher and student menu
- **WHEN** un profesor o estudiante abre la aplicación
- **THEN** conserva Calendario y Avisos como enlaces sueltos y no ve el menú Escuela

### Requirement: Subjects as a list for admin
En `/admin/tasks` las materias SHALL mostrarse como lista paginada (tabla) y en el detalle de la asignatura y de entregas la matrícula SHALL aparecer antes del nombre del estudiante.

#### Scenario: Admin subjects list
- **WHEN** el administrador abre Tareas
- **THEN** ve una tabla de materias con una acción para ver estudiantes y tareas

#### Scenario: Enrollment number first
- **WHEN** se abre el detalle de una asignatura
- **THEN** la tabla de estudiantes muestra la matrícula antes del nombre
