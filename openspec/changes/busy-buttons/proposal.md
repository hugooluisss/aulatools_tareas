# Proposal

## Why

Al pulsar un botón que llama al servidor, el usuario no recibe retroalimentación y puede pulsar dos veces, duplicando altas o envíos. Se necesita deshabilitar el botón mientras la petición está en curso y mostrar qué ocurre.

## What Changes

- Al pulsar cualquier botón que dispare una petición HTTP, el botón se **deshabilita** hasta que terminan sus peticiones (éxito o error).
- Los botones fuera de tablas además **cambian su texto** mientras dura la petición: `Leyendo…` (GET), `Guardando…` (POST, PUT, PATCH), `Procesando…` (DELETE y rutas `/auth/…`).
- Los botones dentro de tablas (icono o texto en `td`/`th`) solo se deshabilitan, sin cambiar su contenido.
- Se implementa de forma global (un servicio y un interceptor HTTP), sin modificar cada componente.

## Capabilities

### New Capabilities
- `busy-buttons`: bloqueo y etiqueta de botones durante peticiones HTTP.

### Modified Capabilities

(Ninguna.)

## Impact

- **Frontend**: servicio `BusyButtonsService`, interceptor HTTP, registro en `app.config.ts`, estilos globales y spec. Sin cambios en backend.
