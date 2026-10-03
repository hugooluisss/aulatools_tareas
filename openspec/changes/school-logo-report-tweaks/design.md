# Design

## Decisiones

1. **Almacenamiento**: igual que la fotografía del profesor: archivo en `backend/public/uploads/school/` con nombre `logo-<escuela>-<hex>.<ext>`, ruta en `schools.logo_path`, tipo verificado con `finfo` (`image/jpeg|png|webp`), máximo 2 MB, borrado del archivo anterior al reemplazar o quitar. Reutilizar la lógica de la fotografía del profesor si se puede extraer sin sobreingeniería (por ejemplo una clase pequeña en `Shared`); no duplicar la validación de tipo y tamaño. Sin recorte en el navegador: el logotipo se sube tal cual.
2. **Respuesta**: `logo_url` (`/uploads/school/<archivo>` o null) en `GET/PUT/POST/DELETE` de `/school`; el frontend lo resuelve con `environment.apiUrl` como la fotografía.
3. **PDF**: dompdf tiene las imágenes remotas desactivadas; el logotipo se incrusta como `data:` URI en base64 leída del disco, con alto máximo de 50 px en el encabezado, a la izquierda del título. Si el archivo no existe o falla la lectura, se omite sin error.
4. **Asistencia**: eliminar la columna `#` (encabezado y celdas) y mantener Matrícula y Estudiante; validación `1 <= days <= 14` en el servicio; el campo del formulario con `max=14` y la misma validación del componente; actualizar los tests y la documentación Bruno que mencionen 31.
5. **Pantalla Escuela**: sección "Logotipo" con selector de archivo, vista previa y botón de quitar, usando `app-icon-button` para la acción de quitar; subida inmediata al elegir el archivo.
