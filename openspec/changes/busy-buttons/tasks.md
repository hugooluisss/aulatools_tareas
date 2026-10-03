# Tasks

Política de pruebas: verificar solo lo que cambia la tarea, sin pruebas exploratorias.

## 1. Frontend

- [x] 1.1 `BusyButtonsService` (listener de clic y control de botones), interceptor HTTP, registro en `app.config.ts` y estilos globales (decisiones 1 a 5). Verificar con un spec del servicio: doble clic envía una petición, se rehabilita tras éxito y tras error, clic sin petición no deshabilita, botón de tabla sin etiqueta, etiquetas por método y `/auth/`.
- [x] 1.2 Confirmar que el envío de formularios con botón `submit` sigue funcionando (decisión 6) y que el login y un botón de tabla del catálogo se comportan como en la especificación. Verificar con los specs del login y de `admin-catalog`.
