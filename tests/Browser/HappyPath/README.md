# Browser Tests - Happy Path

Este directorio contiene los tests E2E (End-to-End) del flujo feliz de la aplicación Amantina App, usando Pest 4 + Playwright.

## 📋 Contenido

### Tests Disponibles

1. **AdminFullFlowTest.php** (18 tests)
   - Flujo completo de configuración del sistema
   - Login, años escolares, lapsos, grados, secciones, usuarios, inscripciones, asignaciones

2. **AdminEditFlowTest.php** (5 tests)
   - Edición de entidades existentes
   - Año escolar, lapso, grado, sección, usuario

3. **AdminDeleteFlowTest.php** (7 tests)
   - Eliminación y soft deletes
   - Año escolar, lapso, grado, sección, usuario, desinscripción, desasignación

4. **AdminNavigationFlowTest.php** (4 tests)
   - Navegación entre módulos
   - Búsqueda y filtros

5. **AdminValidationFlowTest.php** (11 tests)
   - Validación frontend
   - Campos requeridos, fechas, emails, contraseñas, rangos numéricos

### Otros Tests

- Tests de jornadas, asistencia, dashboards (alumno, representante)

**Total**: 55 tests, 262 assertions

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

#### Suite Completa (RECOMENDADO)

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/
```

**Resultado esperado**: 55 tests pasados, 262 assertions, ~6-7 minutos

#### Tests Individuales por Fase

```bash
# Fase 1: Creación (18 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/AdminFullFlowTest.php

# Fase 2: Edición (5 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/AdminEditFlowTest.php

# Fase 3: Eliminación (7 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/AdminDeleteFlowTest.php

# Fase 4: Navegación y Filtros (4 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/AdminNavigationFlowTest.php

# Fase 5: Validación Frontend (11 tests)
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/AdminValidationFlowTest.php
```

#### Ejecutar un Test Específico

```bash
php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/HappyPath/AdminFullFlowTest.php --filter="admin puede crear lapsos académicos"
```

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

## 📚 Documentación Adicional

### Análisis de Cobertura

Ver `COVERAGE_ANALYSIS.md` para:
- Desglose detallado de cada fase
- Progreso y estado de cada test
- Aprendizajes clave
- Convenciones y patrones

### Convenciones de Testing

Ver `AGENTS.md` (raíz del proyecto) para:
- Convenciones generales de testing
- Protocolo de ejecución obligatorio
- Convenciones de Git (ejecutar tests antes de commit)

---

## 🎓 Aprendizajes Clave

### 1. Protocolo de Base de Datos

**Problema**: Laravel Herd cachea configuración con `.env` (desarrollo)

**Solución**: Limpiar cache antes de cada ejecución
```bash
php artisan config:clear
php artisan cache:clear
```

### 2. Patrón de Verificación Robusto

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
    
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
});
```

---

## 🐛 Debugging

### Si un Test Falla

1. **Revisar screenshot**: `tests/Browser/Screenshots/[nombre-del-test].png`
2. **Aumentar wait()** si es problema de timing
3. **Usar screenshot manual** para debugging:
   ```php
   $page->screenshot('debug-punto-X.png');
   ```
4. **Verificar data-test attributes** existen en el HTML

### Modo Headless

Para ejecutar sin ventana del navegador (más rápido):

1. Editar `tests/Pest.php`
2. Comentar la línea `->headed()`
3. Ejecutar tests normalmente

---

## ✅ Estado del Proyecto

**Última actualización**: 2026-05-03

- ✅ 100% cobertura browser real (sin POST directo)
- ✅ 55 tests pasando
- ✅ 262 assertions
- ✅ Base de datos de testing protegida
- ✅ Protocolo documentado

**El sistema está listo para producción desde el punto de vista de testing E2E.** 🎯
