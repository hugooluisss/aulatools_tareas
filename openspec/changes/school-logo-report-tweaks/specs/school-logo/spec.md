# Spec Delta

## Purpose

Logotipo de la escuela y su uso en el PDF de asistencia.

## ADDED Requirements

### Requirement: School logo
El administrador SHALL poder subir un logotipo de la escuela (JPEG, PNG o WebP de hasta 2 MB), reemplazarlo y quitarlo; `GET /school` SHALL devolver `logo_url`.

#### Scenario: Upload logo
- **WHEN** el administrador sube una imagen válida en la pantalla Escuela
- **THEN** la pantalla muestra la vista previa y `GET /school` devuelve `logo_url`

#### Scenario: Invalid file
- **WHEN** el archivo no es una imagen permitida o supera 2 MB
- **THEN** la API responde 400 o 413 y no cambia el logotipo

#### Scenario: Remove logo
- **WHEN** el administrador quita el logotipo
- **THEN** el archivo se borra y `logo_url` es null

### Requirement: Logo in the attendance PDF
La lista de asistencia SHALL mostrar el logotipo de la escuela en el encabezado cuando exista, y SHALL generarse sin él cuando no exista.

#### Scenario: School with logo
- **WHEN** se genera la lista de una escuela con logotipo
- **THEN** el PDF incluye la imagen en el encabezado

### Requirement: Attendance list layout limits
La lista de asistencia SHALL NOT incluir columna de número consecutivo y SHALL aceptar de 1 a 14 días.

#### Scenario: Too many days
- **WHEN** `days` es mayor que 14
- **THEN** la API responde 400

#### Scenario: No row numbers
- **WHEN** se genera la lista
- **THEN** las columnas son Matrícula, Estudiante y las fechas
