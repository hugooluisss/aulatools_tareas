# REST API contract

JSON over HTTPS. Protected routes use `Authorization: Bearer <jwt>`; JWT claims are `sub`, `role` (`admin|teacher|student`), and `school_id`, with an 8-hour expiry. All timestamps are ISO 8601 with timezone; date-only values are `YYYY-MM-DD`. JSON field names use `snake_case`. IDs are opaque integer IDs. Passwords are write-only and never returned.

Errors use `{ "error": { "code": "...", "message": "...", "fields": {"field": "reason"} } }`; `fields` is optional. Error codes: `400` `VALIDATION_ERROR` (malformed/invalid input), `401` `UNAUTHENTICATED` (missing/invalid token or login credentials), `403` `FORBIDDEN` (role/ownership rule), `404` `NOT_FOUND` (missing or deliberately hidden cross-school/resource), `422` `INVALID_STATE` (operation conflicts with resource state). Successful deletes/unenrollments return `204` with no body. List endpoints return `{ "data": [], "meta": {"page":1,"per_page":20,"total":0} }`; `page` and `per_page` are query parameters, default 1 and 20.

## Authentication

| Method / path | Roles | Request | Response |
|---|---|---|---|
| POST `/auth/register-school` | Public | `{school_name, first_name, last_name, email, password}` | `201 {school:{id,name}, user:{id,role:"admin",email,first_name,last_name}, access_token, token_type:"Bearer", expires_in:28800}` |
| POST `/auth/login` | Public | `{email,password}` | `200 {access_token,token_type:"Bearer",expires_in:28800,user:{id,school_id,role,email,first_name,last_name}}` |
| POST `/auth/change-password` | Any authenticated role | `{current_password,new_password}` | `200 {message:"Password updated"}` |

## Users

User object: `{id,school_id,role,email,first_name,last_name}`. Student adds `{enrollment_number,birth_date,status}` (`active|inactive`). Teacher endpoints are admin-only; student endpoints are admin-only. Create accepts `{first_name,last_name,email,password}` for teachers; students also require `enrollment_number,birth_date` and may set `status` (defaults `active`). Update accepts the same profile fields except password; student update may include `status`.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/users/teachers` | Admin | Query pagination | `200` paginated teachers |
| POST `/users/teachers` | Admin | Teacher create JSON | `201 {data:teacher}` |
| GET `/users/teachers/{id}` | Admin | — | `200 {data:teacher}` |
| PUT `/users/teachers/{id}` | Admin | Teacher profile fields | `200 {data:teacher}` |
| DELETE `/users/teachers/{id}` | Admin | — | `204` |
| GET `/users/students` | Admin | Query pagination; optional `status` | `200` paginated students |
| POST `/users/students` | Admin | Student create JSON | `201 {data:student}` |
| GET `/users/students/{id}` | Admin | — | `200 {data:student}` |
| PUT `/users/students/{id}` | Admin | Student profile fields | `200 {data:student}` |
| PATCH `/users/students/{id}/status` | Admin | `{status:"active"|"inactive"}` | `200 {data:student}` |
| DELETE `/users/students/{id}` | Admin | — | `204` |
| PUT `/users/{id}/password` | Admin | `{new_password}` | `200 {message:"Password updated"}` |

## Academic cycles

Cycle object: `{id,school_id,name,starts_on,ends_on,status}` (`active|finished`). Admin manages cycles.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/cycles` | Admin | Query pagination | `200` paginated cycles |
| POST `/cycles` | Admin | `{name,starts_on,ends_on}` | `201 {data:cycle}` (status starts `active`) |
| GET `/cycles/{id}` | Admin | — | `200 {data:cycle}` |
| PUT `/cycles/{id}` | Admin | `{name,starts_on,ends_on}` | `200 {data:cycle}` |
| POST `/cycles/{id}/finish` | Admin | `{}` | `200 {data:cycle}`; all cycle subjects become finished atomically |

## Subjects

Subject object: `{id,cycle_id,teacher_id,name,status}` (`in_progress|finished`). `GET /subjects` is role-filtered: admin gets school subjects, teacher gets assigned subjects, student gets enrolled subjects.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/subjects` | Admin, teacher, student | Query pagination; optional `cycle_id,status` | `200` paginated subjects |
| POST `/subjects` | Admin | `{cycle_id,teacher_id,name}` | `201 {data:subject}` (status `in_progress`) |
| GET `/subjects/{id}` | Admin, assigned teacher, enrolled student | — | `200 {data:subject}` |
| PUT `/subjects/{id}` | Admin | `{cycle_id,teacher_id,name,status}` | `200 {data:subject}` |
| DELETE `/subjects/{id}` | Admin | — | `204` |
| GET `/subjects/{id}/students` | Admin, assigned teacher | Query pagination | `200` paginated student objects |

## Groups and enrollment

Group object: `{id,school_id,name,subjects:[{id,name}]}`. Enrollment endpoints are admin-only.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/groups` | Admin | Query pagination | `200` paginated groups |
| POST `/groups` | Admin | `{name,subject_ids:[id,...]}` | `201 {data:group}` |
| GET `/groups/{id}` | Admin | — | `200 {data:group}` |
| PUT `/groups/{id}` | Admin | `{name,subject_ids:[id,...]}` | `200 {data:group}` |
| DELETE `/groups/{id}` | Admin | — | `204` |
| GET `/groups/{id}/subjects` | Admin | Query pagination | `200` paginated subjects |
| POST `/groups/{id}/students` | Admin | `{student_id}` | `201 {data:{student_id,group_id,subject_ids:[...]}}`; enrolls in all group subjects, idempotently |
| POST `/subjects/{id}/students` | Admin | `{student_id}` | `201 {data:{student_id,subject_id}}`; creates pending deliveries for existing non-cancelled tasks |
| DELETE `/subjects/{id}/students/{student_id}` | Admin | — | `204`; removes only that subject enrollment |

## Tasks and deliveries

Task object: `{id,subject_id,name,description,due_at,status}` (`active|cancelled`). Delivery object: `{id,task_id,student_id,status,delivered_at,grade,overdue,on_time}`. `overdue` is true only when pending and past due; `on_time` is true when delivered at or before `due_at`; otherwise false. Delivery status is `pending|delivered|graded|cancelled`; `grade` is null or 0–100.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/subjects/{subject_id}/tasks` | Admin, assigned teacher, enrolled student | Query pagination | `200` paginated tasks |
| POST `/subjects/{subject_id}/tasks` | Admin, assigned teacher | `{name,description,due_at}` | `201 {data:task}`; creates one pending delivery per enrolled student |
| GET `/tasks/{id}` | Admin, task's teacher, enrolled student | — | `200 {data:task,delivery?:delivery,teacher:{id,first_name,last_name}}` |
| PUT `/tasks/{id}` | Admin, task's teacher | `{name,description,due_at}` | `200 {data:task}` |
| POST `/tasks/{id}/cancel` | Admin, task's teacher | `{}` | `200 {data:task}`; all deliveries become `cancelled` |
| GET `/tasks/{id}/deliveries` | Admin, task's teacher | Query pagination; optional `status` | `200` paginated `{delivery,student:{id,first_name,last_name,enrollment_number}}` rows |
| PUT `/deliveries/{id}/delivered` | Admin, task's teacher | `{}` | `200 {data:delivery}`; sets `delivered_at` to current time |
| PUT `/deliveries/{id}/grade` | Admin, task's teacher | `{grade}` | `200 {data:delivery}`; only a delivered delivery can be graded |
| GET `/me/tasks` | Student | Query pagination; `status` optional, defaults `pending` | `200` paginated `{task,subject:{id,name},delivery}` rows |
| GET `/me/tasks/{delivery_id}` | Student (own delivery) | — | `200 {data:{task,subject,teacher,delivery}}` |

## Delivery comments

Comment object: `{id,delivery_id,author:{id,first_name,last_name,role},body,created_at}`. The delivery's student, its teacher, and school admin can access its thread.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/deliveries/{id}/comments` | Admin, delivery's teacher, owning student | Query pagination | `200` paginated comments |
| POST `/deliveries/{id}/comments` | Admin, delivery's teacher, owning student | `{body}` (non-empty, max 2000 chars) | `201 {data:comment}` |

## Calendar

Event object: `{id,school_id,subject_id,title,description,starts_at,ends_at}` (`subject_id:null` means school-wide). Admin manages events; the view contains visible school/subject events and task due dates applicable to the caller.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/calendar` | Admin, teacher, student | `from`, `to` ISO 8601 query bounds; query pagination | `200` paginated mixed items `{type:"event"|"task_due",id,title,description,starts_at,ends_at,subject_id,task_id?,google_calendar_url?}` |
| GET `/calendar/events` | Admin | Query pagination | `200` paginated events |
| POST `/calendar/events` | Admin | `{subject_id?,title,description,starts_at,ends_at}` | `201 {data:event}` |
| GET `/calendar/events/{id}` | Admin | — | `200 {data:event}` |
| PUT `/calendar/events/{id}` | Admin | `{subject_id?,title,description,starts_at,ends_at}` | `200 {data:event}` |
| DELETE `/calendar/events/{id}` | Admin | — | `204` |

## Announcements

Announcement object: `{id,school_id,title,body,starts_on,ends_on}`. Active means current date is inclusively within the period.

| Method / path | Roles | Request | Response |
|---|---|---|---|
| GET `/announcements` | Admin | Query pagination | `200` paginated all school announcements |
| POST `/announcements` | Admin | `{title,body,starts_on,ends_on}` | `201 {data:announcement}` |
| GET `/announcements/{id}` | Admin | — | `200 {data:announcement}` |
| PUT `/announcements/{id}` | Admin | `{title,body,starts_on,ends_on}` | `200 {data:announcement}` |
| DELETE `/announcements/{id}` | Admin | — | `204` |
| GET `/announcements/active` | Admin, teacher, student | Query pagination | `200` paginated active school announcements |

## Error applicability

Every endpoint may return `401` when authentication is required and absent/invalid, `403` when the caller's role disallows the operation, and `404` for missing or hidden resources. `400` applies to malformed JSON or field validation failures (including invalid dates, duplicate email/enrollment number, invalid grade, or comment body); `422` applies to validly-shaped requests that conflict with state (such as creating a subject in a finished cycle, grading a pending delivery, or finishing an already-finished cycle). Public auth endpoints omit `401` except login, where bad credentials return `401`.
