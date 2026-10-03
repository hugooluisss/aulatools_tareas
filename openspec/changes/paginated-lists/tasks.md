# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias. Nunca modificar datos de negocio.

## 1. Backend

- [x] 1.1 `Shared/Paginator` y migración de todos los servicios paginados, `JsonResponse` y CORS (decisiones backend 1 a 4). Verificar con un test del `Paginator` (límites y página fuera de rango) y los tests de los servicios tocados, más una llamada real a `GET /users/students?page=2&per_page=2` (envoltorio sin cabeceras `X-*`).
- [x] 1.2 Bruno y `docs/api.md` con el nuevo contrato; sin referencias a `X-Total-Count`.

## 2. Frontend: base

- [x] 2.1 `Page<T>`, `PaginatorComponent`, `ColumnComponent` y `DataTableComponent` (decisiones frontend 1 a 4) con sus specs.

## 3. Frontend: migración

- [x] 3.1 Servicios y pantallas del catálogo administrativo (estudiantes, profesores, ciclos, grupos, materias) a `Page<T>` y `DataTableComponent`, y selectores con `per_page=100` (decisiones 5 y 6). Verificar con el spec de `admin-catalog` y de sus servicios.
- [x] 3.2 Avisos, calendario, tareas, entregas, comentarios, notas del estudiante y KPI del inicio a `Page<T>` con `PaginatorComponent` o `DataTableComponent` según sean tablas. Verificar con sus specs.
