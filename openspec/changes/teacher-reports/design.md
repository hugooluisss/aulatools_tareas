# Design

## Context

La asistencia se genera en `backend/src/Reports` con Dompdf y una consulta basada en `enrollments` y `enrollment_subject_bindings`; hoy ruta y pantalla están restringidas a administradores. Los sujetos tienen `teacher_id`; los grupos también pueden tener profesor asignado, y las asignaturas por grupo se representan con `group_subjects`. La boleta consultará `tasks`, `task_deliveries`, estudiantes e inscripciones vinculadas. Véase `proposal.md` y las especificaciones de este cambio.

## Goals / Non-Goals

**Goals:**
- Reusar el PDF y pantalla de asistencia existentes, agregando el alcance docente tanto a consultas como a endpoints de opciones.
- Generar boletas en una solicitud PDF con datos de varios estudiantes seleccionados y separación imprimible por estudiante.
- Separar datos de reporte y formato renderizado para que ambos reportes acepten futuros formatos sin cambiar servicios/controladores.

**Non-Goals:**
- Cambiar el modelo de calificación o estados de tarea.
- Ofrecer reportes para estudiantes o modificar las entregas registradas.

## Decisions

1. **Autorización docente en servicio y consulta.** Mantener el middleware de autenticación y ampliar la restricción de rol a `admin` y `teacher`; validar que la asignatura solicitada tiene `subjects.teacher_id` igual al usuario y que el grupo corresponde a esa asignatura y a sus inscripciones. Las asignaturas/grupos disponibles deben obtenerse en endpoints de consulta acotados por identidad, nunca confiando en filtros del cliente. El administrador conserva el alcance escolar completo. Se prefiere esto a ocultar opciones solo en frontend, que no protege llamadas directas.
2. **Vínculo de asignación.** Usar `subjects.teacher_id` como autoridad de quién enseña la materia. Usar `enrollment_subject_bindings` para identificar estudiantes y asignaciones materia-grupo vigentes; considerar `groups.teacher_id`/binding `teacher_id` solo si el comportamiento existente del dominio los define como delegación válida, evitando ampliar permisos de forma accidental. La comprobación debe armonizarse con la lógica vigente de materias docentes antes de implementar.
3. **Boleta por POST.** Añadir `POST /reports/task-report-card` con `subject_id` y `student_ids` en JSON, evitando límites de longitud de URL cuando hay muchas selecciones. Validar lista no vacía, IDs enteros positivos, ausencia de duplicados y pertenencia de todos los estudiantes a la materia antes de producir el PDF; no generar resultados parciales.
4. **Consulta conjunta para PDF.** Obtener metadatos de escuela/ciclo/materia/docente, lista de estudiantes autorizados y tareas/entregas con consultas acotadas; no hacer una consulta por tarea o estudiante. Para cada estudiante incluir todas las tareas no canceladas, aun cuando no haya fila de entrega, mostrando «—» para fecha y calificación nulas. Mostrar `DATE(due_at)` y solo la parte `YYYY-MM-DD` de `delivered_at`.
5. **ViewModel de datos puros y renderizador intercambiable.** Cada reporte produce un `ReportViewModel` específico (por ejemplo, `AttendanceReportViewModel` y `TaskReportCardViewModel`) desde el servicio y los datos del repositorio. El ViewModel contiene título, campos de encabezado y tablas como encabezados y filas; el de boletas incluye secciones/páginas por estudiante. No contiene HTML ni referencias a Dompdf. Una interfaz `ReportRenderer` recibe el ViewModel y devuelve `RenderedReport` con contenido, tipo de contenido y nombre de archivo. El controlador depende de esa interfaz inyectada y no recibe un parámetro de formato; el binding DI usa `PdfReportRenderer` como implementación por defecto. CSV/Excel futuros pueden consumir el mismo ViewModel sin modificar servicios o controladores.
6. **PDF encapsulado.** `PdfReportRenderer` contiene las plantillas HTML y el uso de Dompdf para ambos ViewModels, mantiene el HTML escapado y las opciones seguras actuales, conserva la salida de asistencia sin cambios y produce una sección por estudiante con `page-break-after` salvo en la última página y nombre de archivo `boleta-tareas-<materia>.pdf`. No instalar otra biblioteca PDF.
7. **Pantallas y rutas.** Mantener el componente y endpoint de asistencia existentes, cambiando permisos y carga de grupos/asignaturas según rol. Añadir pantalla de boleta disponible a admin y teacher, reutilizar el servicio de reportes y los endpoints vigentes para asignaturas/estudiantes donde permitan scope por maestro. Incorporar ambos enlaces al grupo «Reportes» visible para ambos roles. Aplicar patrón de botón ocupado HTTP existente y descarga por Blob.
8. **Bruno y verificación.** Añadir request de Bruno con cuerpo JSON de varios IDs y casos de acceso válido/inválido. Cubrir servicios/controladores y frontend con pruebas específicas de ViewModels sin PDF, un renderizador falso sencillo que consuma un ViewModel, compatibilidad de PDF de asistencia, selección y estados; seguir los comandos de verificación ya usados por los módulos existentes.

## Risks / Trade-offs

- [Los grupos pueden contener materias con docente asignado en la materia o en el grupo] → Aplicar una única regla de autoridad documentada y hacer que consultas de opciones y autorización del reporte usen la misma regla.
- [Una asignatura puede tener muchos estudiantes o tareas] → Hacer consultas por lotes y mantener una sola generación PDF; si el tamaño real resulta excesivo, medir antes de agregar límites que alteren el alcance.
- [Los nombres de estados de entrega pueden incluir cancelación] → Omitir tareas cuyo estado en `tasks` es `cancelled`; los estados de entrega no eliminan una tarea de la tabla, y valores nulos se representan explícitamente.

## Migration Plan

No requiere migración de base de datos ni datos. Desplegar backend (rutas, consultas y PDF) y frontend (rutas, selectores y menú) en la misma versión para que las pantallas no dependan de endpoints ausentes. Rollback mediante reversión de la versión; el cambio no modifica registros de negocio.
