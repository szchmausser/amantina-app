# Dashboard Refactorization - Resumen Completo

**Fecha**: 2026-05-03
**Tipo**: Arquitectura y Datos de Prueba

## Objetivo

Refactorizar los dashboards de admin y profesor para mostrar métricas balanceadas (positivo + negativo) con información accionable, eliminando métricas de vanidad. Enfoque minimalista centrado en lo que requiere acción o reconocimiento.

## Cambios Implementados

### Backend

#### HourAccumulatorService
**Archivo**: `app/Services/HourAccumulatorService.php`

**Método refactorizado**: `getInstitutionOverview()`

**Cambios**:
- Devuelve estudiantes categorizados con datos completos (id, name, hours, percentage, section, grade, status)
- Devuelve secciones categorizadas (top sections ≥80%, concerning sections <60%)
- Cada categoría incluye la lista completa de estudiantes
- Distribución por sección con estudiantes agrupados por estado
- Corregido join con `teacher_assignments` (sections no tienen user_id directo)
- Eliminado código innecesario: termComparison, sessionStats, activityCategoryDistribution, locationDistribution, teacherWorkload, yearOverYear

**Estructura de datos devuelta**:
```php
[
    'totalStudents' => int,
    'requiredHours' => float,
    'averageHours' => float,
    'distribution' => [
        'onTrack' => int,      // ≥80%
        'inProgress' => int,   // 40-79%
        'atRisk' => int,       // <40%
        'noHours' => int,      // 0 horas
    ],
    'onTrackStudents' => [...],
    'inProgressStudents' => [...],
    'atRiskStudents' => [...],
    'outstandingStudents' => [...],  // ≥100%
    'studentsWithNoHours' => [...],
    'topSections' => [...],          // ≥80% promedio
    'concerningSections' => [...],   // <60% promedio
    'alerts' => [
        'zeroHourStudents' => int,
        'sessionsWithoutAttendance' => int,
    ],
]
```

#### DashboardController
**Archivo**: `app/Http/Controllers/DashboardController.php`

**Método actualizado**: `adminDashboard()`

**Cambios**:
- Pasa la nueva estructura de datos al frontend
- Valores por defecto para evitar errores si no hay datos

### Frontend

#### StudentListBadge (Nuevo)
**Archivo**: `resources/js/components/ui/student-list-badge.tsx`

**Funcionalidad**:
- Badge interactivo que abre modal con lista de estudiantes
- Al hacer clic en un estudiante, navega a su detalle (`/admin/students/{id}`)
- Variantes de color según el estado (success, warning, destructive)
- Si count es 0, muestra texto plano sin interacción

#### AdminDashboard (Refactorizado)
**Archivo**: `resources/js/pages/admin/dashboard.tsx`
**Backup**: `resources/js/pages/admin/dashboard-old.tsx`

**Secciones del dashboard**:

1. **Alertas Críticas** (solo si existen)
   - Estudiantes sin horas registradas
   - Sesiones sin registro de asistencia

2. **Distribución de Estudiantes** (badges interactivos)
   - ✅ En Meta (≥80% de la cuota)
   - 🟡 En Progreso (40-79% de la cuota)
   - 🔴 En Riesgo (<40% de la cuota)

3. **Balance Positivo/Negativo** (lado a lado)
   - ⭐ Estudiantes Sobresalientes (≥100% de la cuota)
   - 🆘 Estudiantes que Necesitan Apoyo (<40% de la cuota)

4. **Panorama por Secciones**
   - 🏆 Secciones con Mejor Rendimiento (≥80% promedio)
   - ⚠️ Secciones que Requieren Atención (<60% promedio)
   - Cada sección muestra badges interactivos con distribución de estudiantes

## Seeders - Datos de Prueba

### Archivos Creados

1. **FieldSessionsSeeder.php**
   - Genera 50-100 jornadas de campo con actividades y asistencias
   - 10-40 estudiantes por jornada (de diferentes secciones)
   - 85% de asistencia efectiva
   - 1-3 actividades por estudiante
   - 90% jornadas completadas, 10% canceladas

2. **CompleteTestDataSeeder.php**
   - Orquestador maestro que ejecuta todos los seeders en orden correcto
   - Muestra progreso por pasos
   - Incluye credenciales de acceso al final

3. **SeedCompleteTestData.php** (Comando Artisan)
   - Verifica que existan las tablas antes de ejecutar
   - Opción `--fresh` para limpiar y regenerar
   - Muestra estadísticas al finalizar

4. **README.md** (en database/seeders/)
   - Documentación completa de uso
   - Descripción de datos generados
   - Escenarios de prueba

### Datos Generados (Verificados)

```
✅ 625 Usuarios totales
✅ 585 Estudiantes (500 aleatorios + 85 de prueba)
✅ 30 Profesores (25 aleatorios + 5 de prueba)
✅ 15 Secciones (5 grados × 3 secciones)
✅ 360 Inscripciones activas
✅ 78 Jornadas de campo
✅ 1,825 Asistencias registradas
✅ 3,079 Actividades completadas
```

### Comandos

```bash
# Regenerar todo desde cero
php artisan migrate:fresh --force
php artisan db:seed --class=CompleteTestDataSeeder

# Solo agregar más jornadas
php artisan db:seed --class=FieldSessionsSeeder

# Comando con verificaciones
php artisan db:seed-test-data
php artisan db:seed-test-data --fresh
```

## Aprendizajes Clave

### Estructura de Base de Datos

**field_sessions**:
- NO tiene `section_id` (las jornadas no están atadas a secciones)
- Campos: name, description, academic_year_id, school_term_id, user_id, activity_name, location_name, start_datetime, end_datetime, base_hours, status_id, cancellation_reason

**attendance_activities**:
- NO tiene columna `performance`
- Campos: attendance_id, activity_category_id, hours, notes

**sections**:
- NO tiene `user_id` directo
- Relación con profesores: `Section` -> `TeacherAssignment` -> `User`

**field_session_statuses**:
- Nombres: 'planned', 'realized', 'cancelled' (NO 'completed')

### Modelo de Datos

- **Estudiante es la unidad de medición**, no la sección
- La sección es solo un **agrupador visual**
- Todos los estudiantes tienen la misma cuota (del año académico)
- Las jornadas pueden tener estudiantes de múltiples secciones
- Un profesor puede estar asignado a múltiples secciones

### Umbrales de Desempeño

- **Sobresaliente**: ≥100% (cumplió y superó la meta)
- **En Meta**: 80-99% (va bien, cumplirá)
- **En Progreso**: 40-79% (avanza pero necesita mantener ritmo)
- **En Riesgo**: <40% (difícilmente cumpla sin intervención)

## Acceso al Sistema

```
URL: http://amantina-app.test/dashboard

Credenciales:
- Admin:     admin@example.com / password
- Profesor:  profesor@example.com / password
- Alumno:    alumno@example.com / password
```

## Filosofía del Diseño

**"Si no te hace tomar una decisión o acción, no debería estar en el dashboard"**

### Eliminado (Vanity Metrics):
- ❌ Distribución por categoría de actividad
- ❌ Distribución por ubicación
- ❌ Carga de trabajo por profesor
- ❌ Comparación año-a-año
- ❌ Sesiones completadas/canceladas (dato histórico)
- ❌ Ranking completo de secciones

### Mantenido (Actionable Metrics):
- ✅ Estudiantes en riesgo (requiere intervención)
- ✅ Estudiantes sobresalientes (reconocimiento)
- ✅ Estudiantes sin horas (alerta crítica)
- ✅ Sesiones sin asistencia (tarea pendiente)
- ✅ Secciones bajo 60% (requieren atención)
- ✅ Secciones sobre 80% (modelo a seguir)

## Próximos Pasos Pendientes

1. Refactorizar dashboard de profesor (similar al de admin pero agrupado por sección)
2. Actualizar `getTeacherDashboard()` en HourAccumulatorService
3. Actualizar tests para las nuevas métricas
4. Considerar agregar filtros por período/término

## Notas Técnicas

- Cache limpiado después de cambios: `php artisan config:clear && php artisan cache:clear`
- Frontend compilado automáticamente por Vite en desarrollo
- Badges interactivos usan Dialog de shadcn/ui
- Navegación con `router.visit()` de Inertia
