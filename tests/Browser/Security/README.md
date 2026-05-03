# Security Tests - Control de Acceso y Permisos

Este directorio contiene los tests E2E de seguridad de la aplicación Amantina App, verificando que el sistema RBAC (Role-Based Access Control) funciona correctamente.

## 📋 Contenido

### Tests de Seguridad

1. **DashboardSecurityTest.php** (13 tests)
   - Verifica que cada rol ve su dashboard correcto
   - Verifica que usuarios no autenticados no pueden acceder
   - Verifica que dashboards respetan el año académico seleccionado

2. **UserManagementSecurityTest.php** (16 tests)
   - Verifica permisos de acceso al módulo de usuarios
   - Verifica que solo usuarios con `users.view` pueden ver listado
   - Verifica que alumnos y representantes no pueden acceder

3. **TeacherAssignmentSecurityTest.php** (16 tests)
   - Verifica acceso al módulo de asignaciones de profesores
   - Verifica que solo admin puede gestionar asignaciones
   - Verifica que profesor, alumno y representante no pueden acceder

4. **AdminModulesSecurityTest.php** (24 tests)
   - Verifica acceso a módulos administrativos por rol
   - Verifica que profesores, alumnos y representantes no pueden acceder a configuración del sistema
   - Cubre: años escolares, lapsos, grados, secciones, condiciones de salud

**Total**: **69 tests de seguridad**

---

## 📊 Resultado de la Suite Completa

**Última ejecución**: 2026-05-03

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact
```

**Resultado**:
- ✅ **700 tests pasados** (342 Feature + 358 Browser)
- ❌ **0 tests fallidos**
- ⏭️ **1 test skipped**
- 📊 **2462 assertions**
- ⏱️ **Duración**: 1104.34s (~18 minutos)

**Desglose Browser Tests**:
- HappyPath: 55 tests, 262 assertions
- Security: 69 tests, 229 assertions
- Otros: 234 tests

---

## 🎯 Roles y Permisos

### Roles del Sistema

1. **Admin**
   - Acceso completo a todos los módulos
   - Puede gestionar usuarios, años escolares, lapsos, grados, secciones
   - Puede ver dashboards de todos los roles

2. **Profesor**
   - Puede ver listado de usuarios (`users.view`)
   - Puede ver inscripciones (`enrollments.view`) - para conocer sus alumnos
   - Puede ver asignaciones (`assignments.view`) - para conocer sus secciones
   - Puede crear y gestionar jornadas de campo (`field_sessions.*`)
   - Puede gestionar categorías de actividad (`activity_categories.*`)
   - Puede gestionar ubicaciones (`locations.*`)
   - Puede registrar asistencia (`attendances.*`)
   - NO puede acceder a configuración del sistema (años escolares, lapsos, grados, secciones)
   - NO puede gestionar usuarios, condiciones de salud

3. **Alumno**
   - Puede ver su propio dashboard
   - Puede ver su progreso de horas
   - NO puede acceder a módulos administrativos
   - NO puede ver listado de usuarios

4. **Representante**
   - Puede ver dashboard de sus representados
   - Puede ver progreso de horas de sus representados
   - NO puede acceder a módulos administrativos
   - NO puede ver listado de usuarios

---

## 🚀 Ejecución de Tests

### ⚠️ PROTOCOLO OBLIGATORIO

**SIEMPRE** ejecutar estos comandos ANTES de correr cualquier test:

```bash
php artisan config:clear
php artisan cache:clear
```

### Comandos de Ejecución

#### Suite Completa de Seguridad

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/Security/
```

#### Tests Individuales

```bash
# Dashboard Security (13 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/Security/DashboardSecurityTest.php

# User Management Security (16 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/Security/UserManagementSecurityTest.php

# Teacher Assignment Security (16 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/Security/TeacherAssignmentSecurityTest.php

# Admin Modules Security (29 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/Security/AdminModulesSecurityTest.php
```

---

## 📚 Matriz de Permisos

### Módulos Administrativos

| Módulo | Admin | Profesor | Alumno | Representante |
|--------|-------|----------|--------|---------------|
| `/admin/academic-years` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/school-terms` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/grades` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/sections` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/users` | ✅ | ✅ (solo view) | ❌ 403 | ❌ 403 |
| `/admin/enrollments` | ✅ | ✅ (solo view) | ❌ 403 | ❌ 403 |
| `/admin/teacher-assignments` | ✅ | ✅ (solo view) | ❌ 403 | ❌ 403 |
| `/admin/field-sessions` | ✅ | ✅ (CRUD completo) | ❌ 403 | ❌ 403 |
| `/admin/activity-categories` | ✅ | ✅ (CRUD completo) | ❌ 403 | ❌ 403 |
| `/admin/locations` | ✅ | ✅ (CRUD completo) | ❌ 403 | ❌ 403 |
| `/admin/health-conditions` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

### Dashboards

| Dashboard | Admin | Profesor | Alumno | Representante |
|-----------|-------|----------|--------|---------------|
| `/dashboard` (admin) | ✅ | ❌ | ❌ | ❌ |
| `/dashboard` (teacher) | ❌ | ✅ | ❌ | ❌ |
| `/dashboard` (student) | ❌ | ❌ | ✅ | ❌ |
| `/dashboard` (representative) | ❌ | ❌ | ❌ | ✅ |

---

## 🎓 Aprendizajes Clave

### 1. Verificación de Acceso No Autorizado

**Patrón correcto**:
```php
test('alumno no puede acceder a módulo admin', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $this->visit('/admin/academic-years')
        ->wait(2)
        ->assertSee('403');
});
```

### 2. Verificación de Acceso Autorizado

**Patrón correcto**:
```php
test('profesor puede ver listado de usuarios', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $this->visit('/admin/users')
        ->wait(2)
        ->assertPathIs('/admin/users')
        ->assertSee('Usuarios');
});
```

### 3. Seeders Obligatorios

**Ejecutar en beforeEach**:
```php
beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    
    // Crear usuarios con roles
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});
```

---

## ✅ Estado del Proyecto

**Última actualización**: 2026-05-03

- ✅ Tests de dashboard por rol (13 tests)
- ✅ Tests de acceso no autorizado a usuarios (16 tests)
- ✅ Tests de acceso no autorizado a asignaciones (16 tests)
- ✅ Tests de acceso no autorizado a módulos admin (24 tests)
- ✅ **Total: 69 tests de seguridad, 229 assertions**

**Suite completa**: 700 tests pasados (342 Feature + 358 Browser), 2462 assertions, 0 fallidos

**Cobertura de seguridad**: 100% de módulos administrativos cubiertos

**Permisos del Profesor**:
- ✅ Acceso operativo: enrollments (view), field-sessions (CRUD), activity-categories (CRUD), locations (CRUD)
- ❌ Sin acceso a configuración: academic-years, school-terms, grades, sections, health-conditions
