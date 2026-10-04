# AulaTools Tareas

Aplicación web escolar para organizar ciclos, materias, grupos e inscripciones, y dar seguimiento a tareas, entregas, comentarios, calendario y avisos.

## Funcionalidades por rol

- **Administrador:** catálogos (ciclos, grupos, materias, planes de estudio), estudiantes, profesores, inscripciones y reinscripciones, usuarios administradores, datos generales y logotipo de la escuela, resumen global de tareas con búsqueda y filtro por estado, calendario y avisos de la escuela.
- **Profesor:** sus materias, tareas y entregas (marcar entregada o desentregar, calificar, comentar, ver comentarios nuevos sin leer), notas privadas del estudiante, calendario, avisos y reportes en PDF: lista de asistencia y boleta de tareas por estudiante.
- **Estudiante:** «Mis tareas» como tarjetas con búsqueda y filtro por estado, panel de cada tarea con comentarios y bitácora de cambios, calendario y avisos.

## Arquitectura

- **Frontend:** Angular standalone con rutas por rol; consume la API REST.
- **Backend:** Yii3 organizado por módulos y capas `Controller`, `Service` y `Repository`. Los controladores reciben HTTP, los servicios aplican reglas de negocio y los repositorios consultan MySQL.
- **Base de datos:** MySQL en el host. Docker Compose no crea un contenedor MySQL.

## Inicio

1. Copia `.env.example` a `.env` y configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` y `JWT_SECRET`. Desde los contenedores, MySQL del host se alcanza con `host.docker.internal`; la base usada en desarrollo es `dev_aulatools_tareas`.
2. Instala dependencias PHP y aplica migraciones:

   ```sh
   docker compose run --rm backend composer install
   docker compose run --rm backend vendor/bin/yii-db-migration migrate:up --no-interaction
   ```

3. Levanta backend y servidor Angular:

   ```sh
   docker compose up -d --build
   ```

La API queda en <http://localhost:8090> y Angular en <http://localhost:4330>. El origen de API del frontend está en `frontend/src/environments/environment.ts`.

El correo de recuperación usa Mailpit en desarrollo: `docker compose up -d mailpit`; su interfaz está en <http://localhost:8025>. Compose configura `MAILER_DSN`, `MAIL_FROM` y `APP_URL` con valores predeterminados. El endpoint de solicitud no limita intentos en este alcance.

## Convenciones del proyecto

- **API:** las listas devuelven un sobre paginado `{items, page, per_page, total, total_pages}`; los errores devuelven `{error: {code, message}}`. Las fechas límite de las tareas son solo fecha (`YYYY-MM-DD`). El contrato completo está en `docs/api.md` y las pantallas por rol en `docs/frontend-pages.md`.
- **Reportes:** cada reporte construye un ViewModel de datos puros y un `ReportRenderer` lo convierte en archivo. Hoy solo existe `PdfReportRenderer`; otro formato (CSV, Excel) se agrega con otro renderizador sin tocar servicios.
- **Estados de entrega:** etiquetas y colores viven en la tabla `task_delivery_statuses` y se consultan en `GET /tasks/statuses`; el frontend no los define.
- **Bitácora:** cada acción sobre una entrega registra un evento en `task_delivery_events`, consultable en `GET /deliveries/{id}/history`.
- **Cambios con OpenSpec:** cada funcionalidad se planea en `openspec/changes/<cambio>` (propuesta, diseño, especificaciones y tareas, en español) antes de implementarse.

## Colección de API (Bruno)

`bruno/` contiene la colección de requests por módulo; la configuración local y los tokens están en `bruno/README.md`.

## Datos de prueba

`scripts/seed-test-tasks.php` y `backend/scripts/seed-student-tasks.php` crean tareas de prueba con títulos que empiezan con «Prueba - ». Son idempotentes y usan la misma lógica de servicios que la aplicación. Se ejecutan dentro del contenedor del backend, por ejemplo `docker compose exec -T backend php scripts/seed-student-tasks.php`.

## Publicación en agents-dev

La publicación de desarrollo usa <https://agents-dev.hugosantiago.dev/aulatools-tareas_frontend/> para Angular y <https://agents-dev.hugosantiago.dev/aulatools-tareas_backend> para la API. Caddy conserva el prefijo del frontend y lo elimina para las rutas del backend. El servidor Angular corre en modo desarrollo con recarga en vivo; Docker Compose publica los puertos locales 4330 (frontend) y 8090 (backend), y MySQL sigue en el host.

## Pruebas y formato

```sh
docker compose exec -T backend composer test
cd frontend && npx ng test --watch=false
cd frontend && npx ng build
cd frontend && npx prettier --check "src/**/*.{ts,html,scss}"
docker compose exec -T backend composer format:check
./scripts/e2e.sh
```

Para ejecutar solo lo que cambiaste: `docker compose exec -T backend vendor/bin/phpunit tests/<Archivo>.php` y `cd frontend && npx ng test --watch=false --include='**/<carpeta>/**/*.spec.ts'`.

El E2E usa emails únicos y conserva sus datos en la base; puede ejecutarse de nuevo sin limpiar información.

## Estructura

```text
backend/      API Yii3, migraciones, scripts de datos de prueba y pruebas PHPUnit
bruno/        colección de requests de la API
docs/         contrato de la API y páginas por rol
frontend/     aplicación Angular
openspec/     cambios (propuesta, diseño, especificaciones y tareas) y especificaciones vigentes
scripts/      verificación E2E y datos de prueba
docker-compose.yml
.env.example
```

## Contribuir y licencia

Los cambios se proponen desde un fork; consulta [CONTRIBUTING.md](CONTRIBUTING.md). El proyecto se distribuye bajo la licencia [MIT](LICENSE).
