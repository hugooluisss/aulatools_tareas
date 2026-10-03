# Spec Delta

## Purpose

Respuesta paginada unificada y componentes de paginación.

## ADDED Requirements

### Requirement: Paginated response envelope
Toda lista paginada SHALL responder con `{ items, page, per_page, total, total_pages }`, con `page >= 1`, `per_page` entre 1 y 100 (20 por defecto) y `total_pages = max(1, ceil(total / per_page))`. SHALL NOT enviar cabeceras `X-Total-Count`, `X-Page` ni `X-Per-Page`.

#### Scenario: Second page
- **WHEN** se pide la página 2 de 143 estudiantes con `per_page` 20
- **THEN** la respuesta trae 20 `items`, `page` 2, `total` 143 y `total_pages` 8

#### Scenario: Page out of range
- **WHEN** se pide la página 99 de una lista de 143 registros
- **THEN** la respuesta es 200 con `items` vacío y `total` 143

#### Scenario: Invalid parameters
- **WHEN** `page` es 0 o `per_page` es 101
- **THEN** la API responde 400 con el formato de error estándar

### Requirement: Generic data table
El frontend SHALL ofrecer una tabla genérica que reciba un `Page<T>` y cuyas columnas se declaren como contenido (`<app-column>`) y no como atributo, y que muestre encabezados, filas, estado vacío y el paginador.

#### Scenario: Navigate pages
- **WHEN** el usuario pulsa la página 3 del paginador
- **THEN** la pantalla pide la página 3 y la tabla muestra sus filas

#### Scenario: Empty list
- **WHEN** la lista no tiene registros
- **THEN** la tabla muestra "No hay registros" y no muestra el paginador

### Requirement: Screens use pagination
Las pantallas de estudiantes, profesores, ciclos, grupos, materias, avisos, calendario, tareas, entregas, comentarios y notas SHALL permitir ver todas las páginas de su lista, con 20 registros por página.

#### Scenario: More than 20 students
- **WHEN** la escuela tiene 45 estudiantes
- **THEN** el catálogo de estudiantes muestra 3 páginas y se pueden recorrer
