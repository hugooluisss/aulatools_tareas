# Spec Delta

## Purpose

Datos de contacto de profesores, estudiantes y escuela, y fotografía del profesor.

## ADDED Requirements

### Requirement: Teacher contact data
Un profesor SHALL poder tener dirección, celular y fotografía opcionales, además de su correo y contraseña. El celular SHALL aceptar de 10 a 15 dígitos (ignorando `+`, espacios, guiones y paréntesis) y se SHALL mostrar con un enlace de WhatsApp.

#### Scenario: Save teacher phone
- **WHEN** el administrativo guarda al profesor con el celular "+52 55 1234 5678"
- **THEN** se almacena `525512345678` y la lista muestra un enlace `https://wa.me/525512345678`

#### Scenario: Invalid phone
- **WHEN** el celular tiene menos de 10 dígitos
- **THEN** la API responde 400 y no guarda cambios

#### Scenario: Edit without password
- **WHEN** se edita a un profesor sin enviar contraseña
- **THEN** los datos se guardan y la contraseña no cambia

### Requirement: Teacher photo
El administrador SHALL poder subir una fotografía del profesor (JPEG, PNG o WebP de hasta 2 MB) tras recortarla y ajustarla en el navegador, y quitarla. Una nueva fotografía SHALL reemplazar y borrar la anterior.

#### Scenario: Upload cropped photo
- **WHEN** el administrativo selecciona una imagen, la recorta en 1:1 y confirma
- **THEN** el profesor muestra la fotografía recortada

#### Scenario: Invalid file
- **WHEN** se sube un archivo que no es una imagen permitida o supera 2 MB
- **THEN** la API responde 400 o 413 y no cambia la fotografía

#### Scenario: Remove photo
- **WHEN** el administrativo quita la fotografía
- **THEN** el archivo se borra y `photo_url` es null

### Requirement: School contact data
La escuela SHALL tener nombre (obligatorio), dirección, teléfono y correo (opcionales, correo con formato válido), editables desde la pantalla Escuela.

#### Scenario: Update school
- **WHEN** el administrativo guarda nombre, dirección, teléfono y correo
- **THEN** `GET /school` devuelve los cuatro valores

### Requirement: Student contact data
Un estudiante SHALL poder tener dirección, teléfono de contacto, nombre del tutor y teléfono del tutor, todos opcionales, con la misma validación de teléfonos.

#### Scenario: Save guardian
- **WHEN** el administrativo guarda al estudiante con nombre y teléfono del tutor
- **THEN** ambos se devuelven en las respuestas del estudiante
