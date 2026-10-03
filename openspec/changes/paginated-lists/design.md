# Design

## Offset frente a cursor

Se usa **offset** (`page`, `per_page`):
- Las listas se ordenan por nombre, apellido o fecha, no por id; la paginación por "siguientes N después del último id" exige un orden único y estable por id (o un cursor compuesto con el campo de orden más el id), lo que complica cada consulta.
- La interfaz necesita saltar a una página concreta y mostrar el total de páginas; un cursor solo avanza o retrocede y el total hay que calcularlo aparte de todos modos.
- El tamaño esperado (hasta miles de estudiantes por escuela) con índices en `school_id` y en los campos de orden no justifica el costo de `OFFSET` alto.
- El riesgo conocido del offset (duplicados u omisiones si se insertan registros mientras se navega) es aceptable en listas administrativas.
- Si una lista de solo-anexar (comentarios, notas) crece mucho, se puede cambiar a cursor sin tocar el resto.

## Respuesta

```json
{ "items": [ ... ], "page": 2, "per_page": 20, "total": 143, "total_pages": 8 }
```
`total_pages = max(1, ceil(total / per_page))`. Una página fuera de rango devuelve `items: []` con los demás campos correctos (no es error). Errores de parámetros: 400 con `{error:{code,message}}`.

## Decisiones backend

1. `App\Shared\Paginator` (clase pequeña): `parse(array $query): array{page:int, perPage:int, offset:int}` que valida y lanza `InvalidArgumentException` con el mensaje existente de paginación inválida; y `build(array $items, int $total, int $page, int $perPage): array` que devuelve el envoltorio. Los servicios dejan de repetir la validación y el armado de `meta`.
2. Los repositorios no cambian (`['data' => ..., 'total' => ...]`).
3. `JsonResponse::send` deja de convertir `data`/`meta` en cabeceras: envía el cuerpo tal cual; se elimina esa lógica y las cabeceras `X-*` de `Access-Control-Expose-Headers`.
4. Se migran todos los servicios que hoy devuelven `['data' => ..., 'meta' => ...]` (Groups x2, Subjects, TaskComments, Calendar x2, Announcements, Cycles, Users, Tasks, StudentNotes).

## Decisiones frontend

1. `Page<T>` en `core/models/page.ts`: `{ items: T[]; page: number; per_page: number; total: number; total_pages: number }`. Cada servicio de lista expone `list(params): Observable<Page<T>>` con `T` tipado (se eliminan los `any[]` de esas listas).
2. `PaginatorComponent` (`shared/paginator/`): entradas `page`, `totalPages`, `total` (signals), salida `pageChange`; botones anterior y siguiente más números con elipsis, accesible (`nav` con `aria-label`, `aria-current`), con `app-icon-button` para anterior/siguiente.
3. `DataTableComponent<T>` (`shared/data-table/`): entradas `page: Page<T>` y `loading`, salida `pageChange`; renderiza `<table>` con encabezados, filas, estado vacío ("No hay registros") y el paginador. Las columnas se declaran como contenido proyectado y **no** como atributo:
   ```html
   <app-data-table [page]="students()" (pageChange)="load($event)">
     <app-column header="Nombre"><ng-template let-row>{{ row.first_name }}</ng-template></app-column>
     <app-column header="Acciones"><ng-template let-row><app-icon-button .../></ng-template></app-column>
   </app-data-table>
   ```
   `ColumnComponent` (selector `app-column`) expone `header` y la plantilla de celda (`contentChild(TemplateRef)`); el componente tabla las lee con `contentChildren(ColumnComponent)`. Los botones dentro de las celdas siguen siendo `td` para que apliquen las reglas de botones ocupados.
4. Las pantallas con tabla usan `DataTableComponent`; las listas que no son tablas (avisos, comentarios, eventos) usan `Page<T>` y `PaginatorComponent` solos.
5. Selectores que necesitan todos los registros usan `per_page=100` (un método `all()` o parámetro en el servicio), con una nota de que más de 100 requeriría un endpoint de opciones.
6. Cada pantalla guarda la página actual en un signal, recarga al cambiar de página y vuelve a la primera al filtrar.

## Riesgos

- Cambio de contrato de varias listas a la vez: backend y frontend se publican juntos.
- Selectores limitados a 100 registros.
