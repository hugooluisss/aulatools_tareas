# Spec Delta

## Purpose

Diseño del login y las pantallas de recuperación.

## ADDED Requirements

### Requirement: Split login layout
El login SHALL mostrar una tarjeta de dos columnas con la marca ("aulatools" y "Tareas") a la izquierda y el formulario a la derecha, con los colores de la aplicación, incluyendo el enlace "¿Olvidaste tu contraseña?" debajo del botón de entrar.

#### Scenario: Desktop
- **WHEN** se abre `/login` en una pantalla ancha
- **THEN** se ven la marca y el formulario lado a lado con campos con icono y botón verde

#### Scenario: Forgot link
- **WHEN** el usuario pulsa "¿Olvidaste tu contraseña?"
- **THEN** navega a `/forgot-password`

### Requirement: Mobile layout
En pantallas de menos de 768 px el login y las pantallas de recuperación SHALL apilarse en una columna, con la marca compacta arriba, sin desplazamiento horizontal.

#### Scenario: Phone width
- **WHEN** se abre `/login` con 375 px de ancho
- **THEN** la marca aparece arriba y el formulario ocupa el ancho de la tarjeta sin scroll horizontal
