# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias. Nunca borrar ni modificar datos de negocio.

## 1. Backend

- [x] 1.1 Añadir `dompdf/dompdf` con composer (desde el contenedor del backend) y crear el módulo `Reports` con la generación de fechas hábiles, consulta de estudiantes y plantilla PDF (decisiones 2 a 4). Verificar con un test del servicio: fechas hábiles (inicio en viernes y en fin de semana), validaciones y filtro por asignatura.
- [x] 1.2 Ruta, controlador y respuesta PDF con control de acceso (decisión 1). Verificar con una llamada autenticada de administrador (cabecera `%PDF`) y una de profesor (403).

## 2. Bruno

- [x] 2.1 Request `reports_attendance_get.bru` documentada.

## 3. Frontend

- [x] 3.1 Servicio, pantalla, ruta `/reports/attendance` y menú desplegable "Reportes" (decisión 5). Verificar con el spec de la pantalla y el del shell.
