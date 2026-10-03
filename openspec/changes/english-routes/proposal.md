# Proposal

## Why

Las URLs del sistema mezclan español e inglés: el frontend usa rutas como `/mis-tareas` o `/inscripciones` y el backend conserva `/inscriptions`, aunque el dominio ya usa `enrollment`. Se unifica todo en inglés.

## What Changes

- **BREAKING (API)**: `/inscriptions…` pasa a `/enrollments…` (7 rutas). Es la única ruta del backend que no está en inglés.
- **BREAKING (URLs del frontend)**: todas las rutas del router pasan a inglés (ver design.md). Sin redirecciones desde las URLs antiguas.
- Bruno: carpeta `Inscriptions` pasa a `Enrollments` y las requests usan `/enrollments`.
- Servicio `InscriptionsService` del frontend: solo cambia la URL; los textos de pantalla no cambian.
- Los enlaces del menú, `routerLink`, `router.navigate` y redirecciones internas se actualizan.

## Capabilities

### New Capabilities
- `english-routes`: todas las rutas HTTP del backend y las rutas del router del frontend están en inglés.

### Modified Capabilities

(Ninguna.)

## Impact

- Backend: `config/common/routes.php`, tests que citen `/inscriptions`.
- Frontend: `app.routes.ts`, `shell.component.*`, componentes con enlaces, specs, `inscriptions.service.ts`.
- Bruno y docs que citen las rutas antiguas.
