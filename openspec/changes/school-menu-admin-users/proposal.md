# Proposal

## Why

El menú del administrador mezcla opciones de la escuela (calendario, avisos, profesores, datos generales) sin agrupar, no hay forma de administrar a los demás administradores, la lista de materias de Tareas ocupa mucho espacio en tarjetas y en el detalle de la asignatura el nombre aparece antes que la matrícula.

## What Changes

- **Menú "Escuela"** (desplegable, solo administrador) con: Calendario, Avisos, Profesores, Usuarios y **Generales** (la pantalla actual de Escuela). Las entradas sueltas Calendario, Avisos, Escuela y Profesores del administrador se retiran del menú plano; profesores y estudiantes conservan Calendario y Avisos como enlaces sueltos.
- **Usuarios** (nuevo): el administrador puede listar, crear, editar y eliminar a los administradores de su escuela y restablecer su contraseña. API `/users/admins` con la misma forma que `/users/teachers`. No puede eliminarse a sí mismo ni dejar la escuela sin administradores. Ruta del frontend `/admin-users`.
- **Materias de Tareas para el administrador** (`/admin/tasks`): en lugar de tarjetas, una lista (tabla) con nombre, estado y acción "Ver estudiantes y tareas", con paginación. La vista del profesor no cambia.
- **Detalle de la asignatura y entregas**: en las tablas de estudiantes la columna **Matrícula** va antes que **Estudiante**.

## Capabilities

### New Capabilities
- `school-menu`: menú Escuela y reorganización de la navegación del administrador.
- `admin-users`: administración de administradores.

### Modified Capabilities

(Ninguna.)

## Impact

- **Backend**: `Users` (rol `admin` en servicio, repositorio, controlador y rutas), tests.
- **Bruno y docs**: endpoints `/users/admins`.
- **Frontend**: shell (menú), `app.routes.ts`, catálogo administrativo (tipo `admins`), servicio de administradores, pantallas de tareas.
