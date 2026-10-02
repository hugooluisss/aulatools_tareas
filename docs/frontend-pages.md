# Frontend pages by role

Routes are SPA paths. Data names refer to API objects in `api.md`. Row actions use Bootstrap Icons; each icon button has the listed Spanish tooltip and accessible label.

| Role | Route / page | Data needed | Actions (icon — tooltip) |
|---|---|---|---|
| Public | `/register-school` Registro | — | Registrar (check-circle — Registrar escuela) |
| Public | `/login` Iniciar sesión | — | Iniciar sesión (box-arrow-in-right — Iniciar sesión) |
| Any | `/change-password` Cambiar contraseña | Current user | Guardar (check-circle — Cambiar contraseña) |
| Admin | `/` Inicio | Summary counts, recent tasks/events | — |
| Admin | `/teachers` Profesores | Teacher list | Crear (plus-circle — Agregar profesor); Ver (eye — Ver profesor); Editar (pencil — Editar profesor); Eliminar (trash — Eliminar profesor); Restablecer contraseña (key — Restablecer contraseña) |
| Admin | `/students` Estudiantes | Student list, enrollment number, status | Crear (plus-circle — Agregar estudiante); Ver (eye — Ver estudiante); Editar (pencil — Editar estudiante); Activar/desactivar (person-check/person-dash — Cambiar estado); Eliminar (trash — Eliminar estudiante); Restablecer contraseña (key — Restablecer contraseña) |
| Admin | `/cycles` Ciclos | Cycle list and dates/status | Crear (plus-circle — Agregar ciclo); Editar (pencil — Editar ciclo); Finalizar (check-circle — Finalizar ciclo) |
| Admin | `/subjects` Materias | Subjects, cycle, teacher, status | Crear (plus-circle — Agregar materia); Ver estudiantes (people — Ver estudiantes); Editar (pencil — Editar materia); Eliminar (trash — Eliminar materia) |
| Admin | `/groups` Grupos | Groups and their subjects | Crear (plus-circle — Agregar grupo); Ver (eye — Ver grupo); Editar (pencil — Editar grupo); Eliminar (trash — Eliminar grupo); Inscribir estudiante (person-plus — Inscribir estudiante) |
| Admin | `/tasks` Tareas | Tasks by subject, status and due date | Crear (plus-circle — Agregar tarea); Ver entregas (eye — Ver entregas); Editar (pencil — Editar tarea); Cancelar (x-circle — Cancelar tarea) |
| Admin | `/tasks/:taskId/deliveries` Entregas | Delivery rows and students | Marcar entregada (check2 — Marcar entregada); Calificar (pencil-square — Calificar); Ver detalle (eye — Ver detalle) |
| Admin | `/deliveries/:deliveryId/comments` Comentarios de entrega | Private delivery thread | Agregar comentario (chat-left-text — Agregar comentario) |
| Admin | `/calendar` Calendario | Events and task due dates | Agregar evento (plus-circle — Agregar evento); Editar evento (pencil — Editar evento); Eliminar evento (trash — Eliminar evento); Agregar a Google Calendar (calendar-plus — Agregar a Google Calendar) |
| Admin | `/announcements` Avisos | All announcements and validity periods | Crear (plus-circle — Agregar aviso); Editar (pencil — Editar aviso); Eliminar (trash — Eliminar aviso) |
| Teacher | `/` Inicio | Assigned subjects, upcoming tasks, events | — |
| Teacher | `/subjects` Mis materias | Assigned subjects and enrolled students | Ver estudiantes (people — Ver estudiantes); Ver tareas (eye — Ver tareas) |
| Teacher | `/tasks` Tareas | Tasks for own subjects | Crear (plus-circle — Agregar tarea); Ver entregas (eye — Ver entregas); Editar (pencil — Editar tarea); Cancelar (x-circle — Cancelar tarea) |
| Teacher | `/tasks/:taskId/deliveries` Entregas | Delivery rows, status and grades | Marcar entregada (check2 — Marcar entregada); Calificar (pencil-square — Calificar); Ver detalle (eye — Ver detalle) |
| Teacher | `/deliveries/:deliveryId/comments` Comentarios de entrega | Thread for student delivery | Agregar comentario (chat-left-text — Agregar comentario) |
| Teacher | `/calendar` Calendario | Visible events and task due dates | Agregar a Google Calendar (calendar-plus — Agregar a Google Calendar) |
| Teacher | `/announcements` Avisos | Active announcements | — |
| Student | `/` Inicio | Own upcoming tasks, subjects, events and announcements | — |
| Student | `/my-tasks` Mis tareas | Own deliveries with status filter, grades and due dates | Ver detalle (eye — Ver detalle) |
| Student | `/my-tasks/:deliveryId` Detalle de tarea | Task, subject, teacher and own delivery | Ver comentarios (chat-left-text — Ver comentarios) |
| Student | `/deliveries/:deliveryId/comments` Comentarios | Own delivery thread | Agregar comentario (chat-left-text — Agregar comentario) |
| Student | `/subjects` Materias | Enrolled subjects | Ver detalle (eye — Ver materia) |
| Student | `/calendar` Calendario | Visible events and own task due dates | Agregar a Google Calendar (calendar-plus — Agregar a Google Calendar) |
| Student | `/announcements` Avisos | Active announcements | Ver (eye — Ver aviso) |
