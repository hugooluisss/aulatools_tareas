# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias.

## 1. Backend y Bruno

- [x] 1.1 Renombrar las 7 rutas `/inscriptions…` a `/enrollments…` en `routes.php`, renombrar la carpeta Bruno `Inscriptions` a `Enrollments` con sus URLs y actualizar tests/docs que las citen. Verificar con `grep -rn inscriptions backend/config bruno` sin resultados de rutas y una llamada autenticada a `GET /enrollments`.

## 2. Frontend

- [x] 2.1 Cambiar todas las rutas según el mapa de design.md en `app.routes.ts` y todas las referencias (menú, `routerLink`, `navigate`, `redirectTo`, `rolesFor`) y la URL de `inscriptions.service.ts` a `/enrollments`. Verificar con `grep` de cada ruta antigua sin resultados y los specs de shell y login.
