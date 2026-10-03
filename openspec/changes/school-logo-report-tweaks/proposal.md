# Proposal

## Why

La lista de asistencia debe ser más compacta y llevar la identidad de la escuela: sin columna de número consecutivo, con un máximo de 14 días y con el logotipo de la escuela en el encabezado.

## What Changes

- **Lista de asistencia**: se elimina la columna `#`; `days` pasa a un máximo de **14** (validación del backend y del formulario); `31` deja de ser válido.
- **Logotipo de la escuela**: nuevo campo `logo_url` en `schools`, con endpoints de administrador `POST /school/logo` (multipart, campo `logo`) y `DELETE /school/logo`. Se sube desde la pantalla Escuela (con vista previa) y aparece en el encabezado del PDF de asistencia (si la escuela no tiene logotipo, el PDF sale igual que hoy).
- `GET /school` y `PUT /school` devuelven `logo_url` (`PUT` no lo modifica).

## Capabilities

### New Capabilities
- `school-logo`: logotipo de la escuela y su uso en reportes.

### Modified Capabilities

(Ninguna: los specs principales aún no existen; este cambio reemplaza el límite de 31 días y la columna `#` descritos en `attendance-report`.)

## Impact

- **Base de datos**: migración `M26100600000000AddSchoolLogo` (`schools.logo_path VARCHAR(255) NULL`).
- **Backend**: `Schools` (subida/borrado), `Reports` (PDF), rutas, `.gitignore` de `public/uploads/`; tests.
- **Bruno**: requests de logotipo.
- **Frontend**: pantalla Escuela (subir, vista previa, quitar) y formulario de asistencia (máximo 14).
