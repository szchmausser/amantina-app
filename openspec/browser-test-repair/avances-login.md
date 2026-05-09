# Browser Test Repair - Registro de Avances

## Fecha: 2026-05-09
## Sesión actual: Reparación progresiva de tests HappyPath

---

## ✅ Archivos reparados en esta sesión

### `tests/Browser/HappyPath/LoginTest.php`
- **Estado**: 4 passed, 1 skipped (8 assertions) — 32.59s
- **Cambios**: Eliminada llamada HTTP directa `$this->post('/logout')`, reemplazada por logout real por UI. Eliminado `actingAs` en test de redirección (login real por UI). Actualizados selectores a `data-testid`.

### `tests/Browser/HappyPath/DashboardTest.php`
- **Estado**: 5 passed (9 assertions) — 28.85s
- **Cambios**: Revertido a `actingAs()` (permitido por progresión, login ya probado).

### `tests/Browser/HappyPath/AdminFullFlowTest.php`
- **Estado**: 9 passed (43 assertions) — 173.59s
- **Cambios**: Eliminados `assertDatabaseHas()` y queries Eloquent mid-test, reemplazados por aserciones visuales. Usados nombres únicos con `uniqid()` para evitar UNIQUE constraint. Mantenido `actingAs()` y factories de setup.

---

## 🔴 Resultado suite HappyPath completa

**288 passed, 2 failed, 1 skipped (843 assertions) — 924.47s**

### Tests fallidos identificados

1. **`AdminDeleteFlowTest` — "admin puede desasignar un profesor de una sección"**
   - **Error**: `Expected to see text [Ya asignado] on the page initially`
   - **Causa**: El badge/texto "Ya asignado" ya no existe en la UI (cambio visual)
   - **Archivo**: `tests/Browser/HappyPath/AdminDeleteFlowTest.php:397`

2. **`AdminNavigationFlowTest` — "admin puede navegar desde dashboard a todos los módulos"**
   - **Error**: `Timeout 15000ms exceeded` al hacer click en `text="Gestión de Usuarios"`
   - **Causa**: El selector por texto ya no funciona (cambio en el sidebar/navegación)
   - **Archivo**: `tests/Browser/HappyPath/AdminNavigationFlowTest.php:59`

---

## 🟡 Problema de configuración resuelto

**Base de datos SQLite inconsistente**: El archivo `database/database_testing.sqlite` estaba en estado corrupto/inconsistente, causando errores como:
- `no such table: permissions`
- `UNIQUE constraint failed: permissions.name, permissions.guard_name`
- `UNIQUE constraint failed: activity_categories.name`

**Solución aplicada**: Eliminar `database/database_testing.sqlite` y dejar que los tests lo recreen con migraciones limpias.

**Lección**: Si los browser tests fallan con errores de UNIQUE constraint en tablas que deberían estar limpias, eliminar la BD de testing y re-ejecutar.

---

## 📋 Siguientes pasos

1. **Reparar `AdminDeleteFlowTest.php`**
   - Verificar qué texto/selector muestra la UI actual para el estado "ya asignado"
   - Actualizar el test

2. **Reparar `AdminNavigationFlowTest.php`**
   - Verificar la estructura actual del sidebar
   - Actualizar selectores de navegación

3. **Ejecutar suite `Security/`**
   - Verificar que no haya tests rotos por cambios UI

---

## 🔧 Componentes modificados

- `resources/js/pages/auth/login.tsx` — agregado `data-testid="login-button"`
- `resources/js/components/app-header.tsx` — agregado `data-testid="user-menu-trigger"`
- `tests/Browser/HappyPath/LoginTest.php` — reescrito
- `tests/Browser/HappyPath/AdminFullFlowTest.php` — reescrito
- `database/database_testing.sqlite` — eliminado y recreado
