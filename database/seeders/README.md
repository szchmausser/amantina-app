# Seeders - Datos de Prueba

Este directorio contiene los seeders para poblar la base de datos con datos de prueba realistas.

## 🚀 Uso Rápido

### Opción 1: Comando Artisan (Recomendado)

```bash
# Generar datos de prueba (mantiene datos existentes)
php artisan db:seed-test-data

# Limpiar base de datos y generar datos frescos
php artisan db:seed-test-data --fresh
```

### Opción 2: Seeder Directo

```bash
# Ejecutar seeder completo
php artisan db:seed --class=CompleteTestDataSeeder

# Ejecutar solo jornadas (requiere datos previos)
php artisan db:seed --class=FieldSessionsSeeder
```

## 📊 Datos Generados

El seeder completo (`CompleteTestDataSeeder`) genera:

### Usuarios
- **1 Admin**: admin@example.com / password
- **1 Profesor de prueba**: profesor@example.com / password
- **1 Alumno de prueba**: alumno@example.com / password
- **500 Estudiantes** aleatorios
- **25 Profesores** aleatorios

### Estructura Académica
- **1 Año escolar activo** con 3 lapsos
- **5 Grados** (1er a 5to año)
- **3 Secciones por grado** (A, B, C)
- **20-30 estudiantes por sección**
- **1-3 profesores por sección**

### Jornadas y Actividades
- **8-15 jornadas por sección**
- **70-95% de asistencia** por jornada
- **85% de estudiantes asisten** cuando están registrados
- **1-3 actividades por estudiante** por jornada
- **90% jornadas completadas**, 10% canceladas

### Desempeño Realista
- **60% desempeño excelente**
- **30% desempeño bueno**
- **10% desempeño regular**

## 🎯 Escenarios de Prueba

Los datos generados permiten probar:

### Dashboard Admin
- ✅ Estudiantes sobresalientes (≥100% cuota)
- ✅ Estudiantes en meta (80-99%)
- ✅ Estudiantes en progreso (40-79%)
- ✅ Estudiantes en riesgo (<40%)
- ✅ Estudiantes sin horas
- ✅ Secciones destacadas (≥80% promedio)
- ✅ Secciones que requieren atención (<60% promedio)

### Dashboard Profesor
- ✅ Múltiples secciones asignadas
- ✅ Estudiantes con diferentes niveles de progreso
- ✅ Sesiones pendientes de registro
- ✅ Estudiantes con baja asistencia

### Reportes
- ✅ Distribución por categorías de actividad
- ✅ Distribución por ubicaciones
- ✅ Comparación entre términos
- ✅ Tendencias de asistencia

## 🔧 Seeders Individuales

### Base Configuration
- `InstitutionSeeder` - Datos de la institución
- `RoleAndPermissionSeeder` - Roles y permisos
- `FieldSessionStatusSeeder` - Estados de sesión

### Catálogos
- `ActivityCategorySeeder` - 14 categorías de actividades
- `LocationSeeder` - 10 ubicaciones
- `HealthConditionSeeder` - Condiciones de salud

### Estructura Académica
- `AcademicYearSeeder` - Año escolar activo
- `SchoolTermSeeder` - 3 lapsos
- `GradeSeeder` - 5 grados
- `SectionSeeder` - 3 secciones por grado

### Usuarios y Asignaciones
- `UserSeeder` - Usuarios base del sistema
- `TestUsersSeeder` - Usuarios de prueba
- `DemoDataSeeder` - 500 estudiantes + 25 profesores
- `TeacherAssignmentSeeder` - Asignación de profesores

### Jornadas
- `FieldSessionsSeeder` - Jornadas con actividades y asistencias

## ⚠️ Notas Importantes

1. **Orden de Ejecución**: Los seeders deben ejecutarse en orden. Usa `CompleteTestDataSeeder` para garantizar el orden correcto.

2. **Año Activo**: Debe existir un año escolar activo antes de ejecutar `DemoDataSeeder` o `FieldSessionsSeeder`.

3. **Datos Previos**: `FieldSessionsSeeder` requiere que existan:
   - Estudiantes inscritos
   - Profesores asignados
   - Categorías de actividades
   - Ubicaciones
   - Estados de sesión

4. **Performance**: Generar 500 estudiantes con jornadas puede tomar 1-2 minutos.

## 🧹 Limpiar Base de Datos

```bash
# Limpiar y regenerar todo
php artisan migrate:fresh
php artisan db:seed-test-data

# O en un solo comando
php artisan db:seed-test-data --fresh
```

## 📝 Personalización

Para ajustar la cantidad de datos generados, edita:

- `DemoDataSeeder.php` - Líneas 38-39 (cantidad de estudiantes/profesores)
- `FieldSessionsSeeder.php` - Línea 82 (jornadas por sección)
