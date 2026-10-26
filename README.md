# Sistema RBAC con Laravel, Inertia.js y React

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![React](https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB)
![TypeScript](https://img.shields.io/badge/TypeScript-007ACC?style=for-the-badge&logo=typescript&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

Un sistema de control de acceso basado en roles (RBAC) granular y seguro, desarrollado sobre Laravel 11+, Inertia.js y React (TypeScript). Este proyecto incluye auditoría automatizada de registros y verificación de permisos por módulo a nivel de frontend y backend.

---

## Requisitos Previos

Asegúrate de tener instalado en tu entorno local:

- **PHP**: `>= 8.2`
- **Composer**: `>= 2.0`
- **Node.js**: `>= 18.0` & **npm** / **pnpm** / **yarn**
- **Base de datos**: MySQL `>= 8.0` o PostgreSQL
- **Entorno de desarrollo local**: Laravel Herd, Valet o Laragon (Recomendado: Laravel Herd)

---

## Instalación y Configuración

Sigue estos pasos para levantar el proyecto desde cero:

### 1. Clonar el repositorio
```bash
git clone https://github.com/nwxrd/RBAC-system.git
cd RBAC-system
```

### 2. Instalar dependencias de PHP y JavaScript
```bash
composer install
npm install
```

### 3. Configurar las variables de entorno
Copia el archivo `.env.example` a `.env`:
```bash
cp .env.example .env
```

Abre el archivo `.env` y configura los accesos a tu base de datos:
```env
DB_CONNECTION=
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

### 4. Generar la clave de la aplicación
```bash
php artisan key:generate
```

### 5. Ejecutar migraciones y seeders
Crea las tablas en la base de datos y ejecuta el seeder inicial para poblar los módulos y el usuario **Super Admin**:
```bash
php artisan migrate:fresh --seed
```

---

## Ejecución en Desarrollo

### Opción A: Usando Laravel Herd (Recomendado)
Si estás utilizando **Laravel Herd**, el servidor web PHP ya está corriendo automáticamente. Solo necesitas levantar el servidor de desarrollo para los assets de React:

```bash
npm run dev
```
Accede en tu navegador a: `http://RBAC-system.test`

### Opción B: Usando Artisan Serv
Si no utilizas Herd, ejecuta en dos terminales independientes:

**Terminal 1 (Backend PHP):**
```bash
php artisan serve
```

**Terminal 2 (Frontend React):**
```bash
npm run dev
```
Accede en tu navegador a: `http://127.0.0.1:8000`

---

## Credenciales de Acceso por Defecto

El seeder crea el usuario administrador por defecto con acceso total a la plataforma:

- **Email:** `correo@dominio.cl`
- **Contraseña:** `password`

---

## Arquitectura y Tecnologías

- **Backend:** Laravel 11+
- **Frontend:** React + TypeScript alimentado por Inertia.js
- **Estilos:** Tailwind CSS
- **Seguridad:** Middleware personalizado `CheckModulePermission`
- **Auditoría:** Trait `HasAuditColumns` (registro automático de `created_by` y `updated_by`)
- **Gestión de Permisos Frontend:** Hook personalizado `usePermission`