# Proposal

## Why

Administración necesita imprimir listas de asistencia para un periodo de fechas, por asignatura de un grupo o generales por grupo (algunas escuelas no llevan lista por asignatura). Es el primer reporte del sistema.

## What Changes

- Nuevo endpoint de administrador `GET /reports/attendance` que devuelve un PDF imprimible: estudiantes en filas y fechas en columnas, con casillas vacías para marcar a mano. No se guarda asistencia en el sistema.
- Parámetros: `group_id` (obligatorio), `subject_id` (opcional: sin él, lista general del grupo), `start_date` (`YYYY-MM-DD`) y `days` (cantidad de días hábiles, de 1 a 31). Los sábados y domingos se omiten, de modo que 5 días equivalen a una semana escolar.
- Encabezado del PDF: escuela, ciclo, grupo, asignatura (si aplica) con su profesor, y el periodo.
- Nueva pantalla "Lista de asistencia" en el frontend (ruta `/reports/attendance`) con un menú desplegable "Reportes" para el administrador, que descarga el PDF.
- Dependencia nueva en el backend: `dompdf/dompdf`.

## Capabilities

### New Capabilities
- `attendance-report`: generación en PDF de la lista de asistencia por periodo.

### Modified Capabilities

(Ninguna.)

## Impact

- **Backend**: módulo `Reports` (controlador, servicio, repositorio), ruta, `composer.json`; tests.
- **Bruno**: request nueva.
- **Frontend**: servicio, pantalla, ruta y menú.
- Alcance: solo administrador; profesores quedan fuera por ahora.
