# Spec Delta

## Purpose

Evitar pulsaciones dobles y dar retroalimentación en botones que llaman al servidor.

## ADDED Requirements

### Requirement: Disable buttons during requests
Un botón cuyo clic inicie una petición HTTP SHALL quedar deshabilitado hasta que terminen todas las peticiones iniciadas por ese clic, tanto si tienen éxito como si fallan.

#### Scenario: Double click on save
- **WHEN** el usuario pulsa Guardar y vuelve a pulsarlo antes de la respuesta
- **THEN** solo se envía una petición

#### Scenario: Request fails
- **WHEN** la petición responde con error
- **THEN** el botón vuelve a estar habilitado

#### Scenario: Click without request
- **WHEN** el usuario pulsa Cancelar o abre un modal sin petición
- **THEN** el botón no se deshabilita

### Requirement: Busy label outside tables
Un botón fuera de una tabla SHALL mostrar `Leyendo…`, `Guardando…` o `Procesando…` según la petición (GET, POST/PUT/PATCH, DELETE o `/auth/`), y su texto original SHALL volver al terminar.

#### Scenario: Save button text
- **WHEN** el usuario pulsa Guardar en un formulario
- **THEN** el botón muestra "Guardando…" y está deshabilitado hasta la respuesta

### Requirement: Table buttons only disable
Los botones dentro de tablas SHALL deshabilitarse durante la petición sin cambiar su contenido.

#### Scenario: Delete icon in a table row
- **WHEN** el usuario pulsa el icono de eliminar de una fila
- **THEN** el botón queda deshabilitado, conserva su icono y se rehabilita al terminar
