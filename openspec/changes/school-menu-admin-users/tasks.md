# Tasks

Política de pruebas: verificar solo lo que cambia cada tarea, sin pruebas exploratorias. Nunca modificar datos de negocio; las pruebas de administradores usan usuarios de prueba que se eliminan al terminar.

## 1. Backend

- [x] 1.1 Rol `admin` en `UserService`, `UserRepository`, `UserController` y rutas `/users/admins`, con las reglas de la decisión 2 y restablecer contraseña para administradores. Verificar con tests del servicio (crear, listar, eliminarse a sí mismo, último administrador) y una llamada real a `GET /users/admins`.
- [x] 1.2 Bruno y `docs/api.md` de `/users/admins`.

## 2. Frontend

- [x] 2.1 Menú Escuela, rutas y pantalla Usuarios (decisiones 1 y 3). Verificar con los specs del shell y del catálogo.
- [x] 2.2 Materias en lista para el administrador y matrícula antes del estudiante (decisiones 4 y 5). Verificar con los specs de las pantallas de tareas.
