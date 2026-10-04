# Spec Delta

## Purpose

Permitir que cada docente y administrativo identifique los comentarios de estudiantes que aún no ha consultado en las entregas de tareas.

## ADDED Requirements

### Requirement: Per-user unread student comments
El sistema SHALL calcular comentarios sin leer por usuario y entrega para docentes de la materia y administrativos de la escuela. Solo cuentan comentarios escritos por estudiantes; los comentarios del propio usuario MUST NOT contar como no leídos. La vista de listas SHALL incluir, por entrega, un indicador booleano y un conteo de comentarios de estudiante sin leer, y las tareas de una materia SHALL exponer el conteo de entregas con comentarios sin leer para el usuario actual.

#### Scenario: Student comment is unread
- **WHEN** un estudiante agrega un comentario a una entrega
- **THEN** el docente asignado y cada administrativo autorizado ven esa entrega como «Nuevo» hasta marcar sus comentarios como leídos

#### Scenario: User opens comments
- **WHEN** un docente o administrativo abre el modal de comentarios de una entrega
- **THEN** solo se actualiza el punto de lectura de ese usuario y esa entrega, hasta el comentario de estudiante más reciente existente en ese momento

#### Scenario: New comment after read
- **WHEN** un estudiante comenta después del punto de lectura registrado
- **THEN** el nuevo comentario vuelve a contar como no leído para ese usuario, pero no para usuarios con otro punto de lectura

#### Scenario: Own comments do not count
- **WHEN** el usuario consultante escribió comentarios en la entrega
- **THEN** esos comentarios no se incluyen en su conteo de comentarios sin leer

#### Scenario: Student comment list
- **WHEN** un estudiante consulta comentarios de su propia entrega
- **THEN** la operación de marcar como leídos no cambia ningún indicador de lectura de docentes o administrativos

### Requirement: Explicit comment read operation
El sistema SHALL ofrecer una operación autenticada y autorizada para que el docente de la materia o un administrativo marque como leídos los comentarios de una entrega. El cliente SHALL invocarla al abrir el modal. Consultar el listado paginado de comentarios MUST NOT cambiar por sí mismo el estado de lectura.

#### Scenario: Opening modal marks comments as read
- **WHEN** un usuario autorizado abre el modal de comentarios de una entrega
- **THEN** el sistema registra el comentario de estudiante más reciente existente y confirma la operación

#### Scenario: Unauthorized read update
- **WHEN** un usuario no autorizado solicita marcar una entrega como leída
- **THEN** el sistema conserva el estado y responde con el error de autorización o recurso no encontrado según las reglas existentes de acceso

#### Scenario: Empty thread
- **WHEN** un docente o administrativo abre los comentarios de una entrega sin comentarios de estudiantes
- **THEN** la operación termina correctamente y la entrega permanece sin comentarios nuevos

### Requirement: Modal comments access
Los comentarios SHALL abrirse en un diálogo modal accesible desde la lista de entregas y el detalle de tarea del estudiante. La operación de marcado como leído SHALL ejecutarse al abrir el diálogo. El sistema MUST NOT requerir una ruta frontend independiente para consultar comentarios.

#### Scenario: Open comments from delivery row
- **WHEN** docente o administrativo activa «Comentarios» en una fila de entregas
- **THEN** se abre el diálogo con los comentarios de esa entrega y se marca su punto de lectura

#### Scenario: Open comments from student task detail
- **WHEN** el estudiante activa «Ver comentarios» en el detalle de su tarea
- **THEN** se abre el diálogo de su entrega y se conserva el detalle subyacente

### Requirement: Teacher grading modal
El docente SHALL poder calificar una entrega en estado Entregada desde su acción «Calificar» mediante un diálogo pequeño con entrada numérica de 0 a 100, validación y estado ocupado durante el guardado. Los administrativos MUST NOT tener disponible esta acción y las entregas en otros estados MUST NOT poder calificarse.

#### Scenario: Teacher opens grading dialog
- **WHEN** el docente activa «Calificar» en una entrega Entregada
- **THEN** se abre un modal con entrada numérica y el valor permitido está entre 0 y 100

#### Scenario: Invalid grade
- **WHEN** se intenta guardar un valor vacío, no numérico o fuera del rango 0 a 100
- **THEN** se muestra validación y no se envía la calificación

#### Scenario: Busy grade submission
- **WHEN** el docente guarda una calificación válida
- **THEN** el botón muestra estado ocupado y evita envíos duplicados hasta que termine la solicitud

#### Scenario: Admin cannot grade
- **WHEN** un administrativo ve una fila de entrega
- **THEN** no se muestra una acción de calificación
