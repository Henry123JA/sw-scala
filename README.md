# 🎵 Scala Musical — Sistema de Gestión Académica

**Scala Musical** es una plataforma web integral diseñada para la administración y seguimiento académico de academias de música. Permite gestionar cursos, grupos, horarios, inscripciones, control de asistencias diarias, evaluaciones con libretas de calificaciones y bitácora pedagógica de estudiantes bajo un control de acceso basado en roles (**RBAC**).

El sistema cuenta con una interfaz moderna y reactiva basada en la línea de diseño **Nota Menta Claro** (fresca, limpia y optimizada para entornos educativos).

---

## 🚀 Tecnologías Utilizadas

- **Backend:** [Laravel 12](https://laravel.com/) (PHP 8.2+)
- **Frontend:** [Vue 3](https://vuejs.org/) (Composition API) + [Inertia.js v3](https://inertiajs.com/)
- **Estilos & UI:** [Tailwind CSS v4](https://tailwindcss.com/) + Lucide Icons
- **Base de Datos:** [PostgreSQL](https://www.postgresql.org/)
- **Empaquetador:** [Vite 8](https://vitejs.dev/)

---

## 📋 Módulos Principales

1. **Autenticación y Roles (RBAC):**
   - **Propietario (Admin):** Control total, auditoría (bitácora de accesos) y supervisión global.
   - **Secretaria:** Gestión operativa de alumnos, docentes, cursos, grupos e inscripciones.
   - **Docente:** Gestión de sus grupos asignados, toma de asistencias, evaluaciones y registro de observaciones.
   - **Alumno:** Portal de consulta personal (mis cursos, porcentaje de asistencia, libreta de notas y recomendaciones docentes).
2. **Gestión Académica:**
   - Cursos, Aulas/Salas y Horarios semanales por docente y grupo.
   - Inscripciones con control de estados (`ACTIVA`, `PAUSADA`, `CANCELADA`).
3. **Control de Asistencias (HU8 & HU9):**
   - Panel de grupos en tarjetas interactivas con estado en tiempo real (`Asistencia tomada hoy` vs `Pendiente hoy`).
   - Planilla de marcado rápido (`Presente`, `Atraso`, `Licencia`, `Falta`) con alertas de alumnos pausados/retirados.
   - Historial de asistencia grupal con métricas KPI y revisión de sesiones previas.
4. **Evaluaciones y Calificaciones (HU10):**
   - Creación de evaluaciones por grupo (prácticas, teoría, audiciones, recitales).
   - Planilla para asentamiento de notas (0 a 100) y libreta personal de calificaciones para el estudiante.
5. **Observaciones Académicas (HU12):**
   - Bitácora pedagógica longitudinal del alumno (progreso técnico, recomendaciones, disciplina, felicitaciones) con control de visibilidad para el alumno.

---

## 🛠️ Requisitos Previos

Antes de comenzar, asegúrate de tener instalado en tu equipo:

- **PHP >= 8.2** con las extensiones: `pdo_pgsql`, `mbstring`, `openssl`, `curl`.
- **Composer** (Gestor de dependencias de PHP).
- **Node.js >= 18.x** y **NPM**.
- **PostgreSQL >= 14** en ejecución.

---

## ⚙️ Guía de Instalación Paso a Paso

### 1. Clonar el Repositorio
```bash
git clone <URL_DEL_REPOSITORIO>
cd sw-scala
```

### 2. Instalar Dependencias de PHP
```bash
composer install
```

### 3. Instalar Dependencias de Node.js
```bash
npm install
```

### 4. Configurar Variables de Entorno (`.env`)
Copia el archivo de ejemplo:
```bash
# En Windows (PowerShell):
Copy-Item .env.example .env

# En Linux / macOS / Git Bash:
cp .env.example .env
```

Abre el archivo `.env` y configura los accesos a tu base de datos PostgreSQL y la URL de desarrollo:

```ini
APP_NAME="Scala Musical"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
VITE_BASE_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_scalaMusical
DB_USERNAME=postgres
DB_PASSWORD=tu_password_aqui
```

### 5. Generar la Clave de la Aplicación
```bash
php artisan key:generate
```

### 6. Enlace Simbólico para Almacenamiento (Fotos de Perfil)
```bash
php artisan storage:link
```

### 7. Ejecutar Migraciones y Datos Iniciales (Seeders)
Crea la base de datos `db_scalaMusical` en tu servidor PostgreSQL y luego ejecuta:

```bash
php artisan migrate:fresh --seed
```

> **Nota:** Esto creará todas las tablas del sistema e insertará los roles, permisos, catálogo de cursos, aulas, docentes, grupos y cuentas de prueba.

---

## 🚀 Puesta en Marcha (Servidores de Desarrollo)

Para trabajar localmente, necesitas levantar tanto el servidor de Laravel como el compilador de Vite:

### Opción A: En terminales separadas (Recomendado)

**Terminal 1 (Backend Laravel):**
```bash
php artisan serve
```
*(Inicia en `http://127.0.0.1:8000`)*

**Terminal 2 (Frontend Vite / Hot Reload):**
```bash
npm run dev
```

---

### Opción B: Compilación para Producción (Sin Vite en vivo)
Si solo deseas compilar los archivos del frontend una sola vez:
```bash
npm run build
php artisan serve
```

---

## 👥 Cuentas de Acceso de Prueba (Seeders)

Todos los usuarios de demostración tienen la contraseña por defecto: **`123456`**

| Rol | Correo Electrónico | Contraseña | Permisos / Alcance |
|---|---|:---:|---|
| **Propietario (Admin)** | `admin@academia.com` | `123456` | Acceso total a todos los módulos y bitácora de auditoría. |
| **Secretaria** | `secretaria@academia.com` | `123456` | Gestión de usuarios, grupos, inscripciones y reportes. |
| **Docente** | `carlos.flores@academia.com` | `123456` | Solo ve sus grupos asignados, toma asistencias y califica. |
| **Alumno** | `miguel.salinas@academia.com` | `123456` | Portal personal: mis cursos, libreta de notas y asistencias. |

---

## 🧪 Pruebas Automatizadas

Para correr la suite de pruebas unitarias y de integración de Laravel:

```bash
php artisan test
```

Para ejecutar pruebas individuales por módulo:
```bash
php artisan test --filter=AsistenciaTest
```

---

## 📂 Estructura del Código

```text
sw-scala/
├── app/
│   ├── Http/
│   │   ├── Controllers/   # Controladores por módulo (Asistencia, Evaluacion, etc.)
│   │   ├── Middleware/    # RBAC (CheckRole), Bitácora, Página Visitada
│   │   └── Requests/      # Form Requests para validación de entrada
│   ├── Models/            # Modelos Eloquent y relaciones
│   └── Services/          # Lógica de negocio desacoplada (Arquitectura Service Layer)
├── database/
│   ├── migrations/        # Esquema de base de datos PostgreSQL
│   └── seeders/           # Datos maestros y de demostración
├── resources/
│   ├── css/
│   │   ├── app.css        # Clases utilitarias y tokens Nota Menta
│   │   └── themes.css     # Variables CSS institucionales
│   └── js/
│       ├── Components/    # Modales y componentes reutilizables
│       ├── Pages/         # Vistas Vue 3 organizadas por módulo (Inertia.js)
│       └── config/        # navigation.js (Menú desacoplado gobernado en frontend)
└── routes/
    └── web.php            # Definición centralizada de rutas seguras
```

---

## 📄 Licencia
Este software ha sido desarrollado para fines académicos y de gestión institucional en academias de música.

