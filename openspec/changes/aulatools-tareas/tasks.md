# Tasks

## 1. Infraestructura y andamiaje

- [x] 1.1 Crear `docker-compose.yml`, `.env.example` y `.gitignore` (excluye `.env`, `vendor`, `node_modules`); verificar con `docker compose config`
- [x] 1.2 Crear esqueleto Yii3 en `backend/` con composer en contenedor y estructura `src/<Modulo>/{Controller,Service,Repository}`; verificar que `GET /health` responde 200
- [x] 1.3 Crear esqueleto Angular en `frontend/` (standalone, SCSS, rutas) con Bootstrap 5 y Bootstrap Icons; verificar que `ng build` termina sin errores
- [x] 1.4 Configurar PHPUnit en backend y verificar que corre una prueba de ejemplo
- [x] 1.5 Configurar conexión MySQL por `.env` (credenciales proporcionadas por el usuario) y verificar conexión con un comando de migración vacío

## 2. Base de datos

- [x] 2.1 Migraciones de `schools`, `users`, `students` y verificar `migrate/up` y `migrate/down`
- [x] 2.2 Migraciones de `academic_cycles`, `subjects`, `groups`, `group_subjects`, `enrollments`
- [x] 2.3 Migraciones de `tasks`, `task_deliveries`, `task_comments`, `calendar_events`, `announcements`; verificar FKs e índices con `SHOW CREATE TABLE`

## 3. Backend: servicios base y autenticación

- [x] 3.1 Implementar `JwtService` y `PasswordHasher` (envuelven librerías) con pruebas unitarias
- [x] 3.2 Implementar middleware de autenticación y de rol; pruebas de 401/403
- [x] 3.3 Implementar registro de escuela (Controller/Service/Repository, transaccional); pruebas de éxito, email duplicado y atomicidad
- [x] 3.4 Implementar login y cambio de contraseña propio; pruebas incluyendo estudiante inactivo y contraseña actual incorrecta

## 4. Backend: módulos de negocio

- [x] 4.1 Usuarios: CRUD de profesores y estudiantes, estado, reset de contraseña por admin y aislamiento por escuela; pruebas de acceso cruzado
- [x] 4.2 Ciclos: CRUD y finalización que termina materias en transacción; pruebas
- [x] 4.3 Materias: CRUD, vistas de profesor (solo suyas) y estudiante (solo inscritas); pruebas de 404
- [x] 4.4 Grupos e inscripciones: grupo con materias, inscribir a grupo/materia, quitar de materia, sin duplicados, generación de entregas al inscribir; pruebas
- [x] 4.5 Tareas y entregas: CRUD, cancelación total, marcar entregada, calificar 0-100, indicadores Vencida/A tiempo, listados con filtro por estado y detalle; pruebas
- [x] 4.6 Comentarios por entrega con visibilidad restringida; pruebas de estudiante ajeno y profesor ajeno
- [x] 4.7 Calendario (solo admin crea; vista combinada con vencimientos de tareas y ámbito por materia); pruebas
- [x] 4.8 Avisos con periodo de vigencia y vista para estudiante/profesor; pruebas de fechas

## 5. Frontend: base

- [x] 5.1 Tema Sass con variables de paleta, layout con barra lateral, menú por rol y diseño responsive; verificar render y `ng test` del layout
- [x] 5.2 `TokenStorageService`, interceptor HTTP, `AuthService`, guards por rol; pruebas spec
- [x] 5.3 `IconButtonComponent`, `AppTooltipDirective`, `ToastService`, `GoogleCalendarLinkService`; pruebas spec
- [x] 5.4 Pantallas de registro de escuela, login y cambio de contraseña propio

## 6. Frontend: funcionalidades

- [x] 6.1 Admin: listas y formularios de estudiantes, profesores (con reset de contraseña) y ciclos
- [x] 6.2 Admin: materias, grupos e inscripciones
- [x] 6.3 Estudiante: Mis tareas (filtro por estado, default Pendiente) y detalle con comentarios
- [x] 6.4 Profesor: materias propias, estudiantes inscritos, tareas, marcar entregada, calificar, comentarios
- [x] 6.5 Calendario con botón "Agregar a Google Calendar" y gestión de eventos (admin)
- [x] 6.6 Avisos: gestión (admin) y listado vigente
- [x] 6.6 Dashboard de inicio con tarjetas KPI

## 7. Verificación integral

- [x] 7.1 Ejecutar suites completas (`phpunit`, `ng test`) y `ng build`; todos en verde
- [x] 7.2 Flujo end-to-end con Docker: registrar escuela → crear ciclo/materia/estudiante → inscribir → crear tarea → entregar/calificar; documentar en `README.md` cómo levantar el proyecto
