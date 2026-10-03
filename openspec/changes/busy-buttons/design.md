# Design

## Decisiones

1. **Vinculación clic → petición**: un listener global de `click` en fase de captura en `document` guarda el `button` pulsado (`event.target.closest('button')`) y el instante. Un interceptor HTTP avisa al servicio cuando empieza cada petición; si empieza dentro de los 50 ms siguientes al clic, esa petición queda ligada al botón. Los clics que no inician peticiones (abrir/cerrar modales, cancelar) no se tocan, sin parpadeo. Una petición no iniciada por un clic reciente (cargas de página) no bloquea nada.
2. **Bloqueo**: al ligar la primera petición, el servicio pone `disabled = true` en el botón y la clase `is-busy`; al terminar la última petición ligada (`finalize` del interceptor: éxito, error o cancelación) lo restaura. Se cuentan peticiones por botón. Si el botón fue eliminado del DOM, se ignora sin error. Un botón ya deshabilitado antes del clic se respeta (no se rehabilita si no lo deshabilitó el servicio).
3. **Etiqueta sin tocar el DOM de Angular**: no se reemplaza el contenido del botón (rompería los enlaces de plantilla). Se usa CSS: el botón con `is-busy` oculta su contenido (`color: transparent`, iconos incluidos) y muestra `attr(data-busy-label)` con `::after` centrado. El servicio fija `data-busy-label` y lo quita al terminar. Botones dentro de `td`/`th` (o con `data-busy-silent`) no reciben etiqueta, solo `disabled` y atenuación estándar.
4. **Etiquetas**: GET `Leyendo…`; POST, PUT, PATCH `Guardando…`; DELETE `Procesando…`; cualquier petición a una URL que contenga `/auth/` `Procesando…`. Un botón puede forzar su etiqueta con `data-busy-label` en la plantilla (el servicio no lo sobrescribe).
5. **Registro**: `provideAppInitializer` (o equivalente) instancia el servicio al arrancar; el interceptor se añade en `provideHttpClient(withInterceptors([...]))` junto a los existentes. Fuera de alcance: botones que inician la petición de forma asíncrona tras el clic (por ejemplo tras leer un archivo); quedan sin bloqueo.
6. **Envío de formularios**: el botón de envío se deshabilita en la petición, que se inicia dentro del manejador `ngSubmit`, es decir después del evento de envío, por lo que no se cancela el envío del formulario.

## Riesgos

- La ventana de 50 ms asume que los manejadores inician la petición de forma síncrona (es el caso en este código).
