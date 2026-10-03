# Tasks

## 1. Backend

- [x] 1.1 Implementar `GET /tasks/overview` exclusivo para admin, con scope escolar, una fila por tarea × estudiante activo en materia activa, búsqueda, estado validado y orden estable por vencimiento; responder todas las filas como arreglo JSON.
- [x] 1.2 Agregar request Bruno parametrizable y probar filtros, respuesta plana y ordenamiento.
- [ ] 1.3 Crear y aplicar migración del catálogo `task_delivery_statuses` con colores de fondo y texto; implementar `GET /tasks/statuses` para cualquier usuario autenticado con `{code,label,color,text_color}` y agregar su request Bruno y pruebas.

## 2. Frontend

- [ ] 2.1 Reutilizar la ruta `/admin/tasks` con búsqueda/filtro del servidor, catálogo de estados del API y paginación cliente de 20 filas mediante `DataTableComponent`; reiniciar a página 1 cuando cambien los filtros y probar servicio/componente.
- [ ] 2.2 Mostrar etiqueta, color y color de texto del API en botones de estado y badge de «Entrega», usando un mapa computado por código; cubrirlo con pruebas.
- [ ] 2.4 Abrir desde cada fila un diálogo accesible de solo lectura con datos de la fila, incluyendo descripción; habilitar teclado y cierre con Escape, sin acciones de edición.
- [x] 2.3 Conservar el menú existente sin duplicar la ruta.

## 3. Filtro múltiple por estado

- [x] 3.1 Aceptar una lista de estados separada por comas en el endpoint, validarla y filtrar con `IN`; actualizar pruebas de backend y request Bruno.
- [x] 3.2 Reemplazar el selector por botones multiselección accesibles, hacer la búsqueda de ancho completo y actualizar servicio y pruebas frontend.
- [x] 3.3 Revisar y actualizar la especificación y el diseño para documentar la selección múltiple y el comportamiento sin selección.
