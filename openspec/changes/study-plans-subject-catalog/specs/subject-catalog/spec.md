# Spec Delta

## Purpose

Define las materias como catálogo con clave única, plan opcional y estado, independiente de los ciclos.

## ADDED Requirements

### Requirement: Subject catalog entry
Una materia SHALL tener clave obligatoria única dentro de la escuela, nombre, profesor, plan de estudios opcional y estado `active`/`inactive`. Una materia MUST NOT pertenecer a un ciclo.

#### Scenario: Create subject with plan
- **WHEN** el administrativo crea la materia "Matemáticas" con clave "MAT-101" y un plan
- **THEN** la API responde 201 con la materia activa asociada al plan

#### Scenario: Extracurricular subject without plan
- **WHEN** el administrativo crea una materia sin plan
- **THEN** la materia se crea sin plan

#### Scenario: Duplicate key
- **WHEN** el administrativo usa una clave que ya pertenece a otra materia de su escuela
- **THEN** la API responde 409 con `CONFLICT`

#### Scenario: Inactive plan
- **WHEN** el administrativo asocia una materia a un plan inactivo
- **THEN** la API responde 422 con `INVALID_STATE`

### Requirement: Only active subjects are offered
Las listas y selectores que ofrecen materias para agregar a grupos o estudiantes SHALL mostrar solo materias con estado `active`. La lista de administración de Materias MUST mostrar todas con su estado, plan y clave.

#### Scenario: Group form
- **WHEN** el administrativo abre el formulario de un grupo
- **THEN** solo ve materias activas

### Requirement: Groups choose catalog subjects
Un grupo SHALL conservar su ciclo y elegir materias activas del catálogo sin exigir que las materias pertenezcan al ciclo del grupo.

#### Scenario: Subject of any plan
- **WHEN** el administrativo agrega a un grupo una materia activa sin plan
- **THEN** el grupo la acepta

#### Scenario: Inactive subject
- **WHEN** el administrativo intenta agregar una materia inactiva a un grupo
- **THEN** la API responde 400 con `VALIDATION_ERROR`

### Requirement: Group changes propagate to inscribed students
Al cambiar las materias de un grupo, el sistema SHALL inscribir en las materias nuevas, y quitar de las retiradas, a los estudiantes inscritos en ese grupo (solo las filas originadas por el grupo), en una transacción.

#### Scenario: Add subject to a group with inscribed students
- **WHEN** el administrativo agrega una materia a un grupo con 5 estudiantes inscritos
- **THEN** los 5 quedan inscritos en la materia con el ciclo del grupo

### Requirement: Subject deletion and listing
El sistema SHALL rechazar el borrado de una materia con estudiantes inscritos (409) y la lista de materias MUST incluir `students_count`, `clave`, `plan_id`, nombre del plan y estado; el botón de borrar MUST mostrarse deshabilitado cuando `students_count` sea mayor que 0.

#### Scenario: Delete subject with students
- **WHEN** el administrativo borra una materia con estudiantes
- **THEN** la API responde 409 y la materia permanece
