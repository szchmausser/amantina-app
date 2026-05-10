# Browser Test Repair - Diagnóstico y Avances

## Fecha: 2026-05-09
## Estado: En progreso - Diagnóstico inicial completado

---

## 🟡 NOTA: Configuración de Base de Datos

### Hallazgo
El archivo `.env.testing` usa **SQLite** (`database/database_testing.sqlite`).

### Resolución
El usuario confirma que **esta es una decisión consciente** para esta instalación del proyecto. No es un problema a corregir.

### Impacto observado
El uso de SQLite puede afectar cómo `DatabaseTruncation` limpia datos entre tests, lo que explica el error de UNIQUE constraint en `AdminFullFlowTest` (datos residuales de tests previos).

---

## 🟡 PROBLEMA #2: Violaciones a `browser-test-repair` skill

### Hallazgo en `tests/Browser/HappyPath/LoginTest.php`
- ✅ **Test pasa** (4 passed, 1 skipped)
- ❌ Línea 83: `$this->post('/logout')` — **LLAMADA HTTP DIRECTA** prohibida en browser tests
- ❌ Línea 61: `$this->actingAs($admin)` — no simula un usuario real
- ⚠️ Línea 22: usa `data-test` en lugar de `data-testid`
- ⚠️ Usa `->wait(3)` en lugar de `->waitForText()` / `->waitFor()`

### Hallazgo en `tests/Browser/HappyPath/DashboardTest.php`
- ✅ **Test pasa** (5 passed)
- ❌ Usa `$this->actingAs()` en lugar de login real por UI

### Hallazgo en `tests/Browser/HappyPath/AdminFullFlowTest.php`
- ❌ **1 failed**, 8 passed
- ❌ Múltiples llamadas a `$this->actingAs()`
- ❌ Múltiples llamadas a `$this->assertDatabaseHas()` — verificación en DB, no en UI
- ❌ Usa factories **mid-test** (líneas 725-747, 753-764, 770-781, 793-798, 803-807) para crear datos sin pasar por la UI
- ❌ El test "admin puede configurar toda la estructura académica en secuencia" crea la mayoría de datos via factories, no via navegación real
- ⚠️ Usa `->wait(X)` en lugar de waits semánticos

### Error específico
```
SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: academic_years.name
(Connection: sqlite, Database: database/database_testing.sqlite)
```

Esto ocurre en el test secuencial porque `generateUniqueAcademicYearName()` lee datos residuales de SQLite que `DatabaseTruncation` no limpió correctamente.

---

## 📋 Plan de acción

### Fase 1: Corregir configuración de BD
- [ ] Restaurar `.env.testing` a PostgreSQL
- [ ] Verificar que `amantina_app_testing` existe en PostgreSQL
- [ ] Re-ejecutar tests con PostgreSQL

### Fase 2: Reparar tests por archivo (uno a la vez)
- [ ] `LoginTest.php` — eliminar `$this->post()`, reemplazar `actingAs` con login real
- [ ] `DashboardTest.php` — reemplazar `actingAs` con login real por UI
- [ ] `AdminFullFlowTest.php` — reescribir para que TODO pase por la UI
- [ ] Continuar con los demás archivos de `HappyPath/`
- [ ] Luego `Security/`

### Principios a aplicar
1. **Sin llamadas HTTP directas** (`$this->post()`, `Http::`, etc.)
2. **Sin `assertDatabaseHas`** — verificar en la UI lo que el usuario vería
3. **Sin factories mid-test** — todo estado se crea por la interfaz o en `beforeEach`
4. **Sin `actingAs`** — el usuario hace login real por el formulario
5. **Usar `data-testid`** en lugar de `data-test` o clases CSS
6. **Usar waits semánticos** (`waitForText`, `waitFor`) en lugar de `wait(X)`

---

## 🧪 Tests ejecutados en esta sesión

| Archivo | Resultado | Duración | Notas |
|---------|-----------|----------|-------|
| `LoginTest.php` | ✅ 4 passed, 1 skipped | 25.47s | Tiene violaciones pero pasan |
| `DashboardTest.php` | ✅ 5 passed | 13.92s | Tiene violaciones pero pasan |
| `AdminFullFlowTest.php` | ❌ 1 failed, 8 passed | 168.62s | Fallo por UNIQUE constraint + SQLite |

---

## Siguiente sesión
1. Corregir `.env.testing` a PostgreSQL
2. Re-ejecutar `AdminFullFlowTest.php` para confirmar si el fallo persiste con PostgreSQL
3. Comenzar reparación sistemática de `LoginTest.php`
