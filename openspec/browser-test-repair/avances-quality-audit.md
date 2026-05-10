# Browser Test Repair — Quality Audit (Principle 7)

## Fecha: 2026-05-10
## Estado: ✅ COMPLETADO

---

## Resumen Ejecutivo

Auditoría de calidad de todos los browser tests contra el Principio 7 de la skill browser-testing:
> *"Si esta funcionalidad estuviera completamente rota, este test fallaría?"*

Se encontraron **13 CRÍTICOS** y **16 WARNINGs**. Todos los CRÍTICOS fueron reparados. La mayoría de los WARNINGs también.

---

## Principio 7 — Un test sin alma

Un test **vacío** o **ornamental** tiene alguna de estas características:
- Solo verifica que se llegó a una ruta: `assertPathIs('/usuarios')`
- Solo verifica texto genérico: `assertSee('Guardar')`, `assertSee('Jornadas')`
- Ejecuta una acción pero no verifica el resultado concreto
- No verifica mensajes de error de validación

---

## Archivos reparados y cambios realizados

### CRÍTICOS (tests que pasaban sin importar si la feature funcionaba)

| Archivo | Tests afectados | Problema | Solución |
|---------|----------------|----------|----------|
| `AdminValidationFlowTest.php` | 11 tests | Solo verificaban URL, no mensajes de error de validación | Agregado `assertSee()` con mensajes de error específicos + `noValidate` para bypass de HTML5 + corregida lógica invertida en tests de fecha |
| `RepresentativeDashboardTest.php` | 2 tests | Test de horas solo verificaba `'4'` (generic); test de múltiples jornadas era completamente vacío | Agregado `assertSee('4.0h')`, `assertSee('8.0h')`, `assertSee('Panel del Representante')`, nombre del estudiante |
| `AdminFullFlowTest.php` | 1 test secuencial | Solo verificaba `assertPathIs('/admin/academic-info')` sin verificar datos | Navega a section detail page para verificar profesor y estudiante asignados; verifica nombres de grados, año escolar y estadísticas |
| `FieldSessionTest.php` | 3 tests | Solo verificaban `assertPathIs` + `assertNoJavaScriptErrors` sin datos | Agregado headings de página, nombre de jornada en edición, y empty state message |

### WARNINGs (verificaciones débiles reforzadas)

| Archivo | Tests afectados | Cambio |
|---------|----------------|-------|
| `CatalogTest.php` | 3 tests de listing | Agregado `assertSee()` para título de página y botón de acción |
| `CatalogTest.php` | 1 test de roles | Cambiado `assertSee('Roles')` genérico por `assertSee('Roles del Sistema')` |
| `SectionDefinitionsPageTest.php` | 3 tests | Corregidos nombres de tests engañosos (sidebar → acceso por URL); agregado contexto de página |
| `GradeDefinitionsPageTest.php` | 3 tests | Corregidos nombres de tests; badge de orden no existe en UI; renombrado para reflejar realidad |
| `AttendanceTest.php` | 1 test | Agregado `assertSee('Sin horas')` para verificar estado de asistencia, no solo nombre |

---

## Lecciones aprendidas

1. **HTML5 validation blocks server errors in browser tests**: Los formularios React con `required`, `type="email"`, `minLength` etc. impiden que el submit llegue al servidor. Usar `$page->script('document.querySelector("form").noValidate = true')` para bypassear HTML5 y testear errores del servidor.

2. **Radix Collapsible components start closed**: `assertSee()` no encuentra contenido dentro de `CollapsibleContent` cerrado. Navegar a la página de detalle en su lugar.

3. **Validation messages are in English**: La app tiene `APP_LOCALE=es` pero no hay directorio `lang/es/`. Los mensajes por defecto de Laravel están en inglés. Solo los `messages()` personalizados en FormRequests producen texto en español.

4. **Test names must match what they test**: Tests como `"link appears in sidebar for admin"` que navegan por URL directa sin verificar el sidebar son engañosos. Se renombraron a `"admin puede acceder a la página de X"`.

5. **SQLite corruption under parallel access**: La BD `database_testing.sqlite` se corrompe si se ejecutan tests en paralelo o si hay datos residuales de tests previos. Solución: eliminar el archivo y re-ejecutar.

---

## Resultado final

| Suite | Tests | Passed | Failed | Skipped | Assertions |
|-------|-------|--------|--------|---------|------------|
| HappyPath | 291 | 290 | 0 | 1 | 861+ |
| Security | (no changes) | 69 | 0 | 0 | 235 |

Todos los CRÍTICOS resueltos. La suite completa pasa en ~15 minutos.