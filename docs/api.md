# REST API contract

JSON over HTTPS. Protected routes use `Authorization: Bearer <jwt>`; JWT claims are `sub`, `role` (`admin|teacher|student`), and `school_id`, with an 8-hour expiry. All timestamps are ISO 8601 with timezone; date-only values are `YYYY-MM-DD`. JSON field names use `snake_case`. IDs are opaque integer IDs. Passwords are write-only and never returned.

Errors use `{ "error": { "code": "...", "message": "..." } }`. Successful responses return bare JSON except paginated lists, which return {items: [...], page, per_page, total, total_pages}. page defaults to 1; per_page defaults to 20 and is limited to 1–100. total_pages is at least 1; an out-of-range page returns an empty items array. Successful deletes return `204` with no body.

## Authentication

| Method / path | Roles | Request | Response |
|---|---|---|---|
| POST `/auth/register-school` | Public | `{school_name, first_name, last_name, email, password}` | `201 {message:"School registered."}` |
| POST `/auth/login` | Public | `{email,password}` | `200 {token}` |
| POST `/auth/forgot-password` | Public | `{email}` | `200 {message}` (same response whether email exists) |
| POST `/auth/reset-password` | Public | `{token,password}` | `200 {message}` |
| POST `/auth/change-password` | Any authenticated role | `{current_password,new_password}` | `200 {message:"Password updated"}` |

## Users

User object: `{id,school_id,role,email,first_name,last_name}`. Student adds `{enrollment_number,birth_date,status}` (`active|inactive`). Admin, teacher and student management endpoints are admin-only. Create accepts `{first_name,last_name,email,password}` for admins and teachers; optional profile fields are `address,phone`. Students also require `birth_date` and may set `status` (defaults `active`). Update accepts the same profile fields except password; student update may include `status`.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/users/admins` | Admin | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/users/admins` | Admin | Admin create JSON | `201 admin object` |
| GET `/users/admins/{id}` | Admin | — | `200 admin object` |
| PUT `/users/admins/{id}` | Admin | Admin profile fields | `200 admin object` |
| DELETE `/users/admins/{id}` | Admin | — | `204`; cannot delete self or last school admin (`409`) |
| GET `/users/teachers` | Admin | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/users/teachers` | Admin | Teacher create JSON | `201 teacher object` |
| GET `/users/teachers/{id}` | Admin | — | `200 teacher object` |
| PUT `/users/teachers/{id}` | Admin | Teacher profile fields | `200 teacher object` |
| DELETE `/users/teachers/{id}` | Admin | — | `204` |
| GET `/users/students` | Admin | Query pagination; optional `status` | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/users/students` | Admin | Student create JSON | `201 student object` |
| GET `/users/students/{id}` | Admin | — | `200 student object` |
| PUT `/users/students/{id}` | Admin | Student profile fields | `200 student object` |
| PATCH `/users/students/{id}/status` | Admin | `{status:"active"|"inactive"}` | `200 student object` |
| DELETE `/users/students/{id}` | Admin | — | `204` |
| PUT `/users/{id}/password` | Admin | `{new_password}` | `200 {message:"Password updated"}` |

## Academic cycles

Cycle object: `{id,school_id,name,starts_on,ends_on,status}` (`active|finished`). Admin manages cycles.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/cycles` | Admin | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/cycles` | Admin | `{name,starts_on,ends_on}` | `201` bare cycle object (status starts `active`) |
| GET `/cycles/{id}` | Admin | — | `200 cycle object` |
| PUT `/cycles/{id}` | Admin | `{name,starts_on,ends_on}` | `200 cycle object` |
| POST `/cycles/{id}/finish` | Admin | `{}` | `200 cycle object`; all cycle subjects become finished atomically |

## Subjects

Subject object: `{id,school_id,code,plan_id,plan_name,teacher_id,name,status,students_count}` (`active|inactive`). `GET /subjects` is role-filtered: admin gets school subjects, teacher gets assigned subjects, student gets enrolled subjects.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/subjects` | Admin, teacher, student | Query pagination; optional `cycle_id,status` | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/subjects` | Admin | `{code,teacher_id,name,plan_id?}` | `201` bare subject object (status `active`) |
| GET `/subjects/{id}` | Admin, assigned teacher, enrolled student | — | `200 subject object` |
| PUT `/subjects/{id}` | Admin | `{code,teacher_id,name,plan_id?,status}` | `200 subject object` |
| DELETE `/subjects/{id}` | Admin | — | `204` |
| GET `/subjects/{id}/students` | Admin, assigned teacher | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |

## Groups and enrollment

Group object: `{id,school_id,name,subjects:[{id,name}]}`. Enrollment endpoints are admin-only.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/groups` | Admin | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/groups` | Admin | `{name,subject_ids:[id,...]}` | `201 group object` |
| GET `/groups/{id}` | Admin | — | `200 group object` |
| PUT `/groups/{id}` | Admin | `{name,subject_ids:[id,...]}` | `200 group object` |
| DELETE `/groups/{id}` | Admin | — | `204` |
| GET `/groups/{id}/subjects` | Admin | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/groups/{id}/students` | Admin | `{student_id}` | `201` bare `{student_id,group_id,subject_ids:[...]}`; enrolls in all group subjects, idempotently |
| POST `/subjects/{id}/students` | Admin | `{student_id}` | `201` bare `{student_id,subject_id}`; creates pending deliveries for existing non-cancelled tasks |
| DELETE `/subjects/{id}/students/{student_id}` | Admin | — | `204`; removes only that subject enrollment |

## Tasks and deliveries

Task object: `{id,subject_id,name,description,due_at,status}` (`active|cancelled`). Delivery object: `{id,task_id,student_id,status,delivered_at,grade,overdue,on_time}`. `overdue` is true only when pending and past due; `on_time` is true when delivered at or before `due_at`; otherwise false. Delivery status is `pending|delivered|graded|cancelled`; `grade` is null or 0–100.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/subjects/{subject_id}/tasks` | Admin, assigned teacher, enrolled student | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/subjects/{subject_id}/tasks` | Admin, assigned teacher | `{name,description,due_at}` | `201 task object`; creates one pending delivery per enrolled student |
| GET `/tasks/{id}` | Admin, task's teacher, enrolled student | — | `200` bare `{task,delivery?,teacher}` object |
| PUT `/tasks/{id}` | Admin, task's teacher | `{name,description,due_at}` | `200 task object` |
| POST `/tasks/{id}/cancel` | Admin, task's teacher | `{}` | `200 task object`; all deliveries become `cancelled` |
| GET `/tasks/{id}/deliveries` | Admin, task's teacher | Query pagination; optional `status` | `200` {items:[...],page,per_page,total,total_pages}; rows contain `{delivery,student}` |
| PUT `/deliveries/{id}/delivered` | Admin, task's teacher | `{}` | `200 delivery object`; sets `delivered_at` to current time |
| PUT `/deliveries/{id}/grade` | Admin, task's teacher | `{grade}` | `200 delivery object`; only a delivered delivery can be graded |
| GET `/me/tasks` | Student | Query pagination; `status` optional, defaults `pending` | `200` {items:[...],page,per_page,total,total_pages}; rows contain `{task,subject,delivery} |
| GET `/me/tasks/{delivery_id}` | Student (own delivery) | — | `200` bare `{task,subject,teacher,delivery}` object |

## Delivery comments

Comment object: `{id,delivery_id,author:{id,first_name,last_name,role},body,created_at}`. The delivery's student, its teacher, and school admin can access its thread.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/deliveries/{id}/comments` | Admin, delivery's teacher, owning student | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/deliveries/{id}/comments` | Admin, delivery's teacher, owning student | `{body}` (non-empty, max 2000 chars) | `201 comment object` |

## Calendar

Event object: `{id,school_id,subject_id,title,description,starts_at,ends_at}` (`subject_id:null` means school-wide). Admin manages events; the view contains visible school/subject events and task due dates applicable to the caller.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/calendar` | Admin, teacher, student | `from`, `to` ISO 8601 query bounds; query pagination | `200` {items:[...],page,per_page,total,total_pages}; items contain event/task due fields |
| GET `/calendar/events` | Admin | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/calendar/events` | Admin | `{subject_id?,title,description,starts_at,ends_at}` | `201 event object` |
| GET `/calendar/events/{id}` | Admin | — | `200 event object` |
| PUT `/calendar/events/{id}` | Admin | `{subject_id?,title,description,starts_at,ends_at}` | `200 event object` |
| DELETE `/calendar/events/{id}` | Admin | — | `204` |

## Announcements

Announcement object: `{id,school_id,title,body,starts_on,ends_on}`. Active means current date is inclusively within the period.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/announcements` | Admin | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |
| POST `/announcements` | Admin | `{title,body,starts_on,ends_on}` | `201 announcement object` |
| GET `/announcements/{id}` | Admin | — | `200 announcement object` |
| PUT `/announcements/{id}` | Admin | `{title,body,starts_on,ends_on}` | `200 announcement object` |
| DELETE `/announcements/{id}` | Admin | — | `204` |
| GET `/announcements/active` | Admin, teacher, student | Query pagination | `200` {items:[...],page,per_page,total,total_pages} |

## Additional endpoints

- `GET /reports/attendance`: authenticated attendance PDF report; accepts report filters and returns `application/pdf`.
- `GET|POST /users/students/{id}/notes`: list notes (pagination headers) or create `{body}`; returns bare array/object.
- `POST|DELETE /users/teachers/{id}/photo`: upload/delete teacher photo; upload uses multipart `photo`.
- `POST|DELETE /school/logo`: upload/delete school logo; upload uses multipart `logo`.
- `GET /enrollments`, `POST /enrollments`, `PUT|DELETE /enrollments/{id}`, `POST /enrollments/bulk`, `GET /enrollments/aspirants`, `GET /enrollments/reenrollable`: manage enrollments.
- `POST /subjects/{id}/students/bulk` and `PUT /subjects/{id}/students/teacher`: bulk enrollment and teacher reassignment.

## Error applicability

Every endpoint may return `401` when authentication is required and absent/invalid, `403` when the caller's role disallows the operation, and `404` for missing or hidden resources. `400` applies to malformed JSON or field validation failures (including invalid dates, duplicate email/enrollment number, invalid grade, or comment body); `422` applies to validly-shaped requests that conflict with state (such as creating a subject in a finished cycle, grading a pending delivery, or finishing an already-finished cycle). Public auth endpoints omit `401` except login, where bad credentials return `401`.
