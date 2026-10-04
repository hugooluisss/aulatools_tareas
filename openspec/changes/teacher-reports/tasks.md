# Tasks

## 1. Backend

- [x] 1.1 Crear ViewModels de datos puros para asistencia y boleta, con título, encabezados, tablas y secciones cuando corresponda; verificar sus datos en pruebas sin generar PDF.
- [x] 1.2 Definir `ReportRenderer`/`RenderedReport`, implementar `PdfReportRenderer` con plantillas HTML y Dompdf, y enlazar la interfaz en DI; verificar salida PDF y que un renderizador falso pueda consumir un ViewModel.
- [x] 1.3 Refactorizar asistencia para producir ViewModel y pasar por el renderizador, sin cambiar comportamiento ni salida PDF; verificar que las pruebas existentes sigan pasando y comparar los datos/salida esperados.
- [x] 1.4 Ampliar permisos, validaciones y consultas del reporte de asistencia para admin/docente; verificar con pruebas que el docente obtiene su reporte y recibe 403/404 para grupo o materia ajenos, y el admin conserva acceso.
- [x] 1.5 Exponer las opciones de grupos/asignaturas de asistencia acotadas al docente y reutilizarlas en el selector; verificar con pruebas de consulta por rol y escuela.
- [x] 1.6 Implementar `POST /reports/task-report-card` con validación íntegra de asignatura y estudiantes inscritos, producir el ViewModel y generar una página por estudiante excluyendo tareas canceladas; verificar autorización, selección inválida, fechas date-only y contenido PDF `%PDF`.
- [ ] 1.7 Añadir request de Bruno para generar la boleta por POST y documentar IDs de ejemplo; verificar que el request contiene `subject_id` y `student_ids` como JSON.

## 2. Frontend

- [x] 2.1 Habilitar asistencia para docentes y asegurar que grupo/asignatura provengan de opciones limitadas al usuario; verificar rutas/roles, selectores y descarga PDF con pruebas del componente.
- [x] 2.2 Añadir pantalla y ruta «Boleta de tareas» para admin y docente, con selección individual/todos, mínimo uno, botón ocupado y descarga Blob; verificar selección, validación, estados y descarga con pruebas del componente/servicio.
- [x] 2.3 Mostrar «Reportes» con asistencia y boleta para ambos roles; verificar menú visible para teacher/admin y que las rutas respetan los roles configurados.
