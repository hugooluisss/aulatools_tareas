# Design

## Decisiones

1. **Endpoint**: `GET /reports/attendance?group_id&subject_id&start_date&days`, middleware de autenticación y de rol (solo admin), en un módulo nuevo `backend/src/Reports/` con `ReportController`, `AttendanceReportService` y `AttendanceReportRepository`, siguiendo la estructura de los demás módulos. Respuesta `application/pdf` con `Content-Disposition: attachment; filename="attendance-<group>-<start>.pdf"`. Los errores usan `JsonResponse::error` (400 parámetros, 404 grupo o asignatura de otra escuela).
2. **Estudiantes**: inscripciones (`enrollments`) con `group_id` = grupo. Con `subject_id`, solo quienes tienen vínculo en `enrollment_subject_bindings` para esa asignatura; sin él, todos los de la inscripción. Orden por apellido y nombre. Una sola consulta, sin consultas en bucle. La asignatura debe pertenecer a la misma escuela; el profesor mostrado es `subjects.teacher_id`.
3. **Fechas**: el generador parte de `start_date` y avanza solo por lunes a viernes hasta reunir `days` fechas (validación: formato válido, `1 <= days <= 31`); si `start_date` cae en fin de semana, la primera columna es el siguiente lunes. Cada columna muestra día de la semana abreviado y `dd/mm`. Sin calendario de días festivos.
4. **PDF**: `dompdf/dompdf` a partir de una plantilla HTML simple con tabla, hoja carta horizontal, columna de número y nombre y una columna angosta por fecha; con mucho estudiantes la tabla se reparte en páginas con el encabezado repetido (`thead` con `display: table-header-group`). Todos los textos se escapan con `htmlspecialchars`. Sin imágenes remotas (`isRemoteEnabled` desactivado).
5. **Frontend**: servicio `ReportsService.attendance(params)` con `responseType: 'blob'` y descarga con enlace temporal. Pantalla `reports/attendance` con selector de grupo, selector opcional de asignatura (las del grupo, opción "Lista general del grupo"), fecha de inicio (hoy por defecto) y número de días (5 por defecto). Menú desplegable "Reportes" (admin) con la opción "Lista de asistencia", siguiendo el patrón de "Control escolar".
6. **Permisos de profesores**: fuera de alcance; se evaluará aparte porque `GET /groups` es de administrador.

## Riesgos

- `dompdf` añade peso al backend; se acepta por la simplicidad de generar el PDF desde HTML.
