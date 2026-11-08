# Plan de Implementación Multi-Agente — Ciclo Académico (HU8, HU9, HU10, HU12)

Este plan distribuye el trabajo en **4 Agentes Especializados de forma paralela y desacoplada** para ejecutar las historias de usuario base del ciclo académico en **Scala Musical**, respetando la arquitectura:
`Modelos + Migraciones` $\rightarrow$ `Requests` $\rightarrow$ `Services` $\rightarrow$ `Controllers` $\rightarrow$ `Inertia/Vue 3 (Nota Menta)`.

---

## 1. Delimitación de Alcance y Roadmap Futuro

> [!IMPORTANT]
> **ALINEACIÓN CON FUTURAS HISTORIAS (HU11, HU13, HU14):**
> - **HU11 (Progreso Académico)** calculará evolución y promedios combinando asistencias y calificaciones mediante consultas. Por tanto, las tablas de **HU10** deben guardar datos atómicos limpios (`nota`, `ponderacion`, `fecha`).
> - **HU13 (Seguimiento Académico)** será la ficha integral del alumno (asistencias + notas + observaciones). Por tanto, **HU12** debe estructurar sus observaciones asociadas limpiamente a la inscripción del estudiante.
> - **HU14 (Reportes Académicos)** centralizará exportaciones masivas a PDF/Excel por período, grupo y estudiante.
> 
> **DIRECTRIZ ESTRICTA**: Para este sprint nos limitamos estrictamente a la **operación transaccional y consulta directa de HU8, HU9, HU10 y HU12**. **NO** construir motores complejos de exportación ni reportes globales avanzados aquí, ya que eso le corresponde a **HU14**.

---

## 2. Mapa de Historias y Alcance de este Bloque

| Historia | Puntos | Estado Actual | Objetivo del Agente (Sin solaparse con HU14) |
|---|:---:|---|---|
| **HU8: Asistencia** | 8 | ✅ **Completado (95%)** | **Agente 1**: Blindaje con tests automatizados y validación de inscripciones inactivas en planilla. |
| **HU9: Historial de Asistencia** | 5 | ⚠️ **Pendiente Vista Grupal** | **Agente 2**: Vista de consulta histórica de asistencias por grupo y fecha para Docente y Admin (auditoría operativa en pantalla, **sin** exportaciones de HU14). |
| **HU10: Evaluaciones** | 5 | ❌ **Pendiente** | **Agente 3**: Esquema `evaluacion` y `calificacion`, registro de notas de clase/parciales por el docente y libreta personal de consulta para el alumno. |
| **HU12: Observaciones Académicas** | 5 | ❌ **Pendiente** | **Agente 4**: Registro y visualización cronológica de anotaciones pedagógicas por alumno (técnica, recomendaciones, disciplina). Base limpia para HU13. |

---

## 3. Asignación Detallada por Agente

### 🤖 AGENTE 1 — HU8: Cierre y Blindaje de Asistencia (8 pts)
* **Objetivo**: Asegurar robustez transaccional y cobertura de pruebas.
* **Tareas**:
  1. **Tests Automatizados (`tests/Feature/AsistenciaTest.php`)**:
     - Configurar pruebas unitarias/feature de toma de asistencia:
       - Creación de sesión con `firstOrCreate`.
       - Bloqueo 403 a docentes que intenten registrar en grupos no asignados.
       - Bloqueo 403 a alumnos en rutas de mutación `POST /asistencias/sesion` y `PUT /asistencias/{sesion}`.
       - Redirección al Index con mensaje flash de éxito.
  2. **Aviso de estado en planilla (`Show.vue`)**:
     - Si un alumno tiene inscripción `PAUSADA` o `CANCELADA`, mostrar una pequeña etiqueta de advertencia para que el docente sepa su situación académica.
* **Archivos asignados**:
  - `tests/Feature/AsistenciaTest.php`
  - `resources/js/Pages/Asistencia/Show.vue`

---

### 🤖 AGENTE 2 — HU9: Historial de Asistencia Operativo (5 pts)
* **Objetivo**: Consulta histórica en pantalla de asistencias pasadas por grupo (Docente/Admin), delimitado a la operativa diaria (sin entrar en el módulo de reportería de HU14).
* **Tareas**:
  1. **Backend (`AsistenciaService.php`)**:
     - Método `obtenerHistorialGrupo(int $grupoId, ?string $desde, ?string $hasta, Usuario $usuario)`:
       - Autorización: Docente solo sus grupos, Admin/Secretaria cualquier grupo.
       - Listado de sesiones pasadas del grupo con conteo de presentes, faltas, atrasos y licencias.
  2. **Controlador y Ruta**:
     - `GET /asistencias/grupos/{grupo}/historial` $\rightarrow$ `AsistenciaController@historialGrupo`.
  3. **Frontend Nota Menta (`resources/js/Pages/Asistencia/HistorialGrupo.vue`)**:
     - Encabezado con datos del curso y grupo.
     - Lista/Timeline de sesiones pasadas del grupo con fecha y resumen de asistencia.
     - Botón para abrir directamente cualquier sesión pasada en modo edición/consulta.
* **Archivos asignados**:
  - `app/Services/AsistenciaService.php`
  - `app/Http/Controllers/AsistenciaController.php`
  - `resources/js/Pages/Asistencia/HistorialGrupo.vue`

---

### 🤖 AGENTE 3 — HU10: Evaluaciones y Calificaciones (5 pts)
* **Objetivo**: Sistema transaccional de notas por grupo, preparando el insumo numérico que consumirán HU11 (Progreso) y HU13 (Seguimiento).
* **Tareas**:
  1. **Base de Datos & Modelos**:
     - Migración `create_evaluacion_table.php`:
       - `id`, `grupo_id`, `nombre` (string 100), `tipo` (`PARCIAL`, `AUDICION`, `TEORIA`, `RECITAL`), `fecha` (date), `ponderacion` (decimal/entero). Clave foránea a `grupo`.
     - Migración `create_calificacion_table.php`:
       - `id`, `evaluacion_id`, `inscripcion_id`, `nota` (decimal 5,2), `comentario` (string 255, nullable). Unique `['evaluacion_id', 'inscripcion_id']`.
     - Modelos: `app/Models/Evaluacion.php` y `app/Models/Calificacion.php`.
  2. **Backend**:
     - `app/Services/EvaluacionService.php`:
       - `listarEvaluaciones(int $grupoId, Usuario $usuario)`: Docente solo sus grupos.
       - `crearEvaluacion(array $datos, Usuario $usuario)`
       - `guardarCalificaciones(int $evaluacionId, array $notas, Usuario $usuario)`: `upsert` transaccional.
       - `obtenerCalificacionesAlumno(Usuario $usuario)`: Consulta de notas por inscripción para el rol `Alumno`.
     - `app/Http/Controllers/EvaluacionController.php`:
       - `index`, `storeEvaluacion`, `show` (planilla de notas), `guardarNotas`.
  3. **Frontend (Nota Menta)**:
     - `resources/js/Pages/Evaluacion/Index.vue`: Tarjetas de grupos para calificaciones + listado de evaluaciones.
     - `resources/js/Pages/Evaluacion/Show.vue`: Planilla de calificación para ingresar notas de los alumnos del grupo.
     - Sección Alumno: Vista de solo lectura con su libreta de notas por curso.
* **Archivos asignados**:
  - `database/migrations/2026_11_08_000001_create_evaluacion_table.php`
  - `database/migrations/2026_11_08_000002_create_calificacion_table.php`
  - `app/Models/Evaluacion.php`
  - `app/Models/Calificacion.php`
  - `app/Services/EvaluacionService.php`
  - `app/Http/Controllers/EvaluacionController.php`
  - `resources/js/Pages/Evaluacion/`

---

### 🤖 AGENTE 4 — HU12: Observaciones Académicas (5 pts)
* **Objetivo**: Bitácora de anotaciones pedagógicas por alumno (técnica, disciplina, progreso musical), dejando el modelo listo para consolidarse en la ficha integral de HU13.
* **Tareas**:
  1. **Base de Datos & Modelo**:
     - Migración `create_observacion_academica_table.php`:
       - `id`, `inscripcion_id`, `docente_id`, `fecha` (date), `tipo` (`PROGRESO_TECNICO`, `DISCIPLINA`, `RECOMENDACION`, `FELICITACION`), `titulo` (string 150), `descripcion` (text), `visible_para_alumno` (boolean default true).
     - Modelo: `app/Models/ObservacionAcademica.php`.
  2. **Backend**:
     - `app/Services/ObservacionAcademicaService.php`:
       - `listarPorInscripcion(int $inscripcionId, Usuario $usuario)`: Si es Alumno, solo devuelve las que tienen `visible_para_alumno == true`. Si es Docente/Admin, devuelve todas.
       - `crear(array $datos, Usuario $usuario)`: Solo Docente del grupo o Admin.
     - Controlador `ObservacionAcademicaController.php`.
  3. **Frontend (Nota Menta)**:
     - Componente modal o página `resources/js/Pages/Observacion/Index.vue`:
       - Timeline de notas pedagógicas con badges de categoría y fecha.
       - Formulario sencillo para que el Docente añada notas de desempeño.
* **Archivos asignados**:
  - `database/migrations/2026_11_08_000003_create_observacion_academica_table.php`
  - `app/Models/ObservacionAcademica.php`
  - `app/Services/ObservacionAcademicaService.php`
  - `app/Http/Controllers/ObservacionAcademicaController.php`
  - `resources/js/Pages/Observacion/`

---

## 4. Matriz de Integración sin Conflictos

```
[MÓDULO ASISTENCIA: HU8 & HU9]
   ├── Agente 1: AsistenciaService + tests (HU8)
   └── Agente 2: AsistenciaController@historialGrupo (HU9)
   
[MÓDULO EVALUACIONES: HU10]
   └── Agente 3: EvaluacionService + EvaluacionController (Base de cálculo para HU11)

[MÓDULO OBSERVACIONES: HU12]
   └── Agente 4: ObservacionAcademicaService (Base de bitácora para HU13)
```

Cada agente trabaja en sus propios archivos. La navegación en `navigation.js` solo se actualizará agregando los accesos:
- `Evaluaciones` (`roles: ['Propietario', 'Secretaria', 'Docente', 'Alumno']`)
- `Observaciones` (acceso contextual desde la lista de alumnos / ficha de inscripción).
