# Proposal

## Why

Las escuelas necesitan un lugar único donde almacenar los datos académicos de sus estudiantes (materias, tareas, calificaciones, calendario y avisos). Hoy no existe la aplicación; se construye desde cero como plataforma multi-escuela.

## What Changes

- Nueva aplicación web: backend API en Yii3 (PHP) y frontend SPA en Angular, MySQL como base de datos.
- Multi-escuela: una sola BD; `school_id` solo en las tablas raíz, el resto hereda la escuela por FK (3FN).
- Tres roles: administrativo, profesor, estudiante. Login por email + contraseña (JWT).
- Registro público de escuela que crea la escuela y su primer administrativo.
- Catálogo de ciclos escolares; al finalizar un ciclo todas sus materias pasan a Terminada.
- Materias (En curso / Terminada), grupos (conjunto de materias) e inscripciones a grupo o a materia individual.
- Tareas por materia con una fila por estudiante (estado, calificación, fecha de entrega); comentarios por tarea visibles solo para el estudiante autor y el profesor (y el administrativo).
- Calendario administrado por el administrador, con botón "Agregar a Google Calendar" (enlace de plantilla, sin OAuth).
- Avisos con periodo de vigencia para la comunidad estudiantil.
- Diseño inspirado en la referencia (barra lateral morada, tarjetas redondeadas) con Bootstrap 5 e íconos Bootstrap Icons con tooltip.
- Librerías de terceros aisladas detrás de servicios propios (frontend y backend).
- **Fuera de alcance**: módulo de asistencias, calificación final por materia, recuperación de contraseña por correo, subida de archivos, OAuth con Google.

## Capabilities

### New Capabilities
- `school-registration`: alta pública de una escuela junto con su primer administrativo.
- `authentication`: login por email/contraseña, JWT y cambio de contraseña propio.
- `user-management`: CRUD de profesores y estudiantes, estado activo/inactivo y cambio de contraseña por el administrativo.
- `academic-cycles`: catálogo de ciclos escolares y su estado.
- `subjects`: materias, profesor asignado y estado En curso/Terminada.
- `groups-enrollment`: grupos de materias e inscripciones de estudiantes a grupo o materia.
- `tasks`: tareas por materia, entregas por estudiante, estados, calificaciones y listados.
- `task-comments`: comentarios por tarea entre estudiante, profesor y administrativo.
- `calendar`: eventos importantes y enlace a Google Calendar.
- `announcements`: avisos con periodo de visibilidad.
- `frontend-ui`: lineamientos de UI (layout, Bootstrap, íconos con tooltip, estructura de componentes).

### Modified Capabilities

## Impact

- Código nuevo: `backend/` (Yii3), `frontend/` (Angular), `docker-compose.yml` para ambos (esta máquina no tiene php/composer/ng; se usa Docker).
- Base de datos MySQL del equipo local (credenciales pendientes, vía `.env`).
- Dependencias nuevas: Yii3 y un paquete JWT (tras servicio), Angular, Bootstrap 5, Bootstrap Icons.
