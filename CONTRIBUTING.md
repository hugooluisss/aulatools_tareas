# Guía de contribución

Gracias por colaborar con AulaTools Tareas. Esta guía describe cómo proponer y entregar cambios.

## Flujo con fork

Los cambios no se hacen directamente en el repositorio principal: **siempre se trabaja desde un fork**.

1. Haz un fork del repositorio en GitHub.
2. Clona tu fork y agrega el repositorio principal como remoto `upstream`:

   ```sh
   git clone <url-de-tu-fork>
   git remote add upstream <url-del-repositorio-principal>
   ```

3. Crea una rama desde la última versión de `main` de `upstream`; usa un nombre corto que describa el cambio (`feat/boleta-tareas`, `fix/filtro-estados`).
4. Haz tus cambios, súbelos a tu fork y abre un Pull Request hacia `main` del repositorio principal.
5. Mantén tu rama actualizada con `upstream/main` mientras el PR esté abierto.

## Cómo se planea un cambio

Cada funcionalidad o corrección relevante se describe primero con OpenSpec en `openspec/changes/<cambio>`:

- `proposal.md`, `design.md`, `specs/` y `tasks.md`, escritos en español.
- Las palabras clave estructurales y `SHALL`/`MUST` se mantienen en inglés (ver `openspec/config.yaml`).
- Las tareas son pequeñas, con casillas, y cada una indica cómo se verifica.

Los cambios triviales (typos, ajustes de estilo) no necesitan OpenSpec.

## Commits

- Usa Conventional Commits: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`.
- Formato: `<tipo>(<alcance>): <descripción>`; el alcance es opcional.
- El cuerpo tiene una o dos frases que explican el **porqué**, no un resumen del diff.
- No agregues atribución a herramientas de IA, ni `Co-Authored-By`, ni notas de «generado por» en commits ni en Pull Requests.

## Estilo de código

- **General:** código legible, una sentencia por línea, sin expresiones densas. Reutiliza lo que ya existe antes de agregar algo nuevo.
- **Frontend (Angular):** Prettier (`cd frontend && npm run format`); SCSS solo con las variables y tokens de `src/styles`, clases en BEM con kebab-case, sin estilos en línea; HTML con un elemento por línea y bloques `@if` / `@for`.
- **Backend (Yii3):** PSR-12 con `composer format`. Mantén la separación `Controller` → `Service` → `Repository`: los controladores reciben HTTP, los servicios aplican reglas de negocio y los repositorios consultan MySQL con parámetros.

> `composer format` y `npm run format` reformatean todo el árbol. Si tu cambio es acotado, formatea solo los archivos que tocaste para no meter ruido al diff.

## Pruebas

Ejecuta las pruebas de lo que cambiaste antes de abrir el PR:

```sh
docker compose exec -T backend vendor/bin/phpunit tests/<Archivo>.php
cd frontend && npx ng test --watch=false --include='**/<carpeta>/**/*.spec.ts'
```

La suite completa y las verificaciones de formato están en el [README](README.md#pruebas-y-formato). PHP se ejecuta siempre dentro del contenedor del backend.

## Base de datos y configuración

- Las migraciones son reversibles (`up` y `down`) y viven en `backend/migrations`.
- MySQL corre en el host; no agregues un contenedor de MySQL a Docker Compose.
- Las credenciales y secretos solo van en `.env`, que no se versiona. Nunca los escribas en código, documentos ni commits.

## API

Si cambias un endpoint, actualiza en el mismo PR:

- `docs/api.md` (contrato) y `docs/frontend-pages.md` si cambia una pantalla.
- La colección Bruno en `bruno/`.

## Interfaz y accesibilidad

- Textos de la interfaz en español.
- Contraste suficiente, foco visible y uso completo con teclado.
- Diseño que funcione en escritorio y móvil.
- Los botones que disparan peticiones deben mostrar su estado ocupado.

## Antes de abrir el Pull Request

- [ ] El cambio tiene su OpenSpec o es trivial.
- [ ] Sin lógica duplicada, consultas dentro de bucles ni excepciones silenciadas.
- [ ] Pruebas relacionadas en verde y casos límite cubiertos.
- [ ] Formato aplicado solo a los archivos que tocaste.
- [ ] Documentación y Bruno actualizados si cambió la API.
- [ ] Sin credenciales, archivos temporales ni cachés.

## Licencia

Al contribuir aceptas que tu aportación se publique bajo la licencia [MIT](LICENSE) del proyecto.
