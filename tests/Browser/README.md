# Browser Tests - Amantina App

Tests E2E (End-to-End) de la aplicación Bitácora Socioproductiva usando Pest 4 + Playwright.

## 📁 Estructura Organizada

```
tests/Browser/
├── HappyPath/              # Tests funcionales E2E (happy path)
│   ├── Admin*Test.php      # Tests de flujos administrativos
│   ├── Teacher*Test.php    # Tests de flujos de profesores
│   ├── Student*Test.php    # Tests de flujos de estudiantes
│   ├── Representative*Test.php # Tests de flujos de representantes
│   ├── Login*Test.php      # Tests de autenticación
│   ├── Dashboard*Test.php  # Tests de dashboards
│   ├── Profile*Test.php    # Tests de perfil de usuario
│   ├── *Test.php           # Otros tests funcionales
│   ├── README.md           # Guía de tests funcionales
│   ├── COVERAGE_ANALYSIS.md # Análisis de cobertura
│   └── RESUMEN_EJECUTIVO.md # Resumen ejecutivo
│
├── Security/               # Tests de seguridad RBAC
│   ├── DashboardSecurityTest.php # Acceso a dashboards por rol
│   ├── UserManagementSecurityTest.php # Acceso a usuarios
│   ├── TeacherAssignmentSecurityTest.php # Acceso a asignaciones
│   ├── AdminModulesSecurityTest.php # Acceso a módulos admin
│   └── README.md           # Guía de tests de seguridad
│
├── AUDIT_REPORT.md         # Reporte de auditoría
├── QUICK_START.md          # Guía rápida
├── README.md               # Este archivo
└── TESTING_ROADMAP.md      # Roadmap de testing
```

---

## 🎯 Tipos de Tests

### 1. Tests Funcionales (HappyPath/)

**Objetivo**: Verificar que los flujos de usuario funcionan correctamente de principio a fin.

**Cobertura**:
- ✅ Flujos administrativos completos (CRUD de todas las entidades)
- ✅ Flujos de profesores (jornadas, asistencia)
- ✅ Flujos de estudiantes (dashboard, progreso)
- ✅ Flujos de representantes (dashboard de representados)
- ✅ Autenticación y perfiles
- ✅ Navegación y filtros
- ✅ Validación frontend

**Total**: ~65 archivos de tests funcionales

### 2. Tests de Seguridad (Security/)

**Objetivo**: Verificar que el sistema RBAC (Role-Based Access Control) funciona correctamente.

**Cobertura**:
- ✅ Control de acceso a dashboards por rol
- ✅ Control de acceso a módulos administrativos
- ✅ Verificación de permisos específicos
- ✅ Verificación de respuestas 403 (Forbidden)

**Total**: 69 tests de seguridad

---

## 📊 Resultado de la Suite Completa

**Última ejecución**: 2026-05-03

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact
```

**Resultado**:
- ✅ **700 tests pasados**
- ❌ **0 tests fallidos**
- ⏭️ **1 test skipped**
- 📊 **2462 assertions**
- ⏱️ **Duración**: 1104.34s (~18 minutos)

**Desglose por tipo**:
- Feature tests: 342 pasados
- Browser tests: 358 pasados
  - HappyPath: 55 tests
  - Security: 69 tests
  - Otros: 234 tests

---

## 🚀 Ejecución de Tests

### ⚠️ PROTOCOLO OBLIGATORIO

**SIEMPRE** ejecutar estos comandos ANTES de correr cualquier test:

```bash
php artisan config:clear
php artisan cache:clear
```

**¿Por qué es obligatorio?**
- Laravel Herd cachea la configuración con `.env` (desarrollo)
- Sin limpiar cache, los tests usan `amantina_app` (desarrollo) en lugar de `amantina_app_testing`
- Esto causa **PÉRDIDA DE DATOS** en la base de datos de desarrollo

### Comandos de Ejecución

#### Suite Completa (Todos los Tests)

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/
```

**Resultado esperado**: 358 tests pasados (55 HappyPath + 69 Security + 234 otros)

#### Suite de Tests Funcionales (HappyPath)

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/
```

**Resultado esperado**: ~55 tests pasados

#### Suite de Tests de Seguridad (Security)

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/Security/
```

**Resultado esperado**: 69 tests pasados

#### Test Individual

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/AdminFullFlowTest.php
```

---

## 📚 Documentación Detallada

### Tests Funcionales (HappyPath/)

Ver `tests/Browser/HappyPath/README.md` para:
- Descripción detallada de cada archivo de tests
- Comandos de ejecución específicos
- Patrones y convenciones
- Aprendizajes clave

### Tests de Seguridad (Security/)

Ver `tests/Browser/Security/README.md` para:
- Matriz completa de permisos por rol
- Descripción de cada archivo de tests
- Comandos de ejecución específicos
- Patrones de tests de seguridad

---

## 🔧 Configuración

### Base de Datos de Testing

Los tests usan una base de datos PostgreSQL separada: `amantina_app_testing`

**Crear la base de datos** (solo una vez):

```bash
psql -U postgres -c "CREATE DATABASE amantina_app_testing;"
```

O desde pgAdmin:
1. Click derecho en "Databases"
2. Create → Database
3. Nombre: `amantina_app_testing`

### Archivos de Configuración

- `.env.testing` - Configuración de testing (usa `amantina_app_testing`)
- `phpunit.xml` - Configuración de PHPUnit/Pest
- `tests/Pest.php` - Configuración de Pest Browser

---

## 🎓 Aprendizajes Clave

### 1. Protocolo de Base de Datos

**Problema**: Laravel Herd cachea configuración con `.env` (desarrollo)

**Solución**: 
- Limpiar cache antes de cada ejecución
- NO usar `->withHost()` en Pest browser config
- Pest usa su servidor interno que respeta `.env.testing`

### 2. Verificación Robusta

**NO confiar en flash messages** (desaparecen rápido):
```php
// ❌ FLAKY
$page->assertSee('Actualizado correctamente');

// ✅ DETERMINISTA
expect($page->url())->not->toContain('/edit');
$this->assertDatabaseHas('table', ['id' => $id, 'field' => 'new value']);
```

### 3. Selects de shadcn

**Usar selector específico** para evitar ambigüedad:
```php
// ❌ AMBIGUO
$page->click('text="1er Año"');

// ✅ ESPECÍFICO
$page->click('[role="option"]:has-text("1er Año")');
```

### 4. Seeders Obligatorios

**Ejecutar en beforeEach** para crear roles y datos base:
```php
beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    $this->seed(\Database\Seeders\TermTypeSeeder::class);
    $this->seed(\Database\Seeders\FieldSessionStatusSeeder::class);
});
```

---

## ✅ Estado del Proyecto

**Última actualización**: 2026-05-03

### Tests Funcionales (HappyPath/)
- ✅ 100% cobertura browser real (sin POST directo)
- ✅ 55 tests pasando
- ✅ Todas las fases completadas (creación, edición, eliminación, navegación, validación)

### Tests de Seguridad (Security/)
- ✅ 100% cobertura de módulos administrativos
- ✅ 69 tests pasando
- ✅ Matriz completa de permisos documentada

### Suite Completa
- ✅ **700 tests pasando** (342 Feature + 358 Browser)
- ✅ **2462 assertions**
- ✅ **0 tests fallidos**
- ✅ Duración: ~18 minutos

### Organización
- ✅ Estructura limpia: solo 2 carpetas (HappyPath/ y Security/)
- ✅ Documentación completa en cada carpeta
- ✅ Protocolo de ejecución documentado

**El sistema está listo para producción desde el punto de vista de testing E2E.** 🎯

---

## 📞 Soporte

Para preguntas sobre los tests:
- Tests funcionales: `tests/Browser/HappyPath/README.md`
- Tests de seguridad: `tests/Browser/Security/README.md`
- Protocolo general: Este archivo
- Memoria persistente: Engram (aprendizajes guardados)
