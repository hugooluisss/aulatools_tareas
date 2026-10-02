# AulaTools Tareas

Aplicación web escolar para organizar ciclos, materias, grupos e inscripciones, y dar seguimiento a tareas, entregas, comentarios, calendario y avisos.

## Arquitectura

- **Frontend:** Angular standalone con rutas por rol; consume la API REST.
- **Backend:** Yii3 organizado por módulos y capas `Controller`, `Service` y `Repository`. Los controladores reciben HTTP, los servicios aplican reglas de negocio y los repositorios consultan MySQL.
- **Base de datos:** MySQL en el host. Docker Compose no crea un contenedor MySQL.

## Inicio

1. Copia `.env.example` a `.env` y configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` y `JWT_SECRET`. Desde los contenedores, MySQL del host se alcanza con `host.docker.internal`; la base usada en desarrollo es `dev_aulatools_tareas`.
2. Instala dependencias PHP y aplica migraciones:

   ```sh
   docker compose run --rm backend composer install
   docker compose run --rm backend vendor/bin/yii migrate/up
   ```

3. Levanta backend y servidor Angular:

   ```sh
   docker compose up -d --build
   ```

La API queda en <http://localhost:8090> y Angular en <http://localhost:4330>. El origen de API del frontend está en `frontend/src/environments/environment.ts`.

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

El E2E usa emails únicos y conserva sus datos en la base; puede ejecutarse de nuevo sin limpiar información.

## Estructura

```text
backend/      API Yii3, migraciones y pruebas PHPUnit
docs/         contrato de la API
frontend/     aplicación Angular
openspec/     propuesta, especificaciones y tareas del cambio
scripts/      verificaciones E2E
docker-compose.yml
.env.example
```
