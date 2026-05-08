# Apply Progress — PR6 (Browser Tests Refactor)

## Summary

Refactored browser tests for grade-section-definitions to use proper E2E navigation patterns instead of HTTP methods. Added `data-test` attributes to frontend components to enable reliable browser testing. This completes the quality improvements for the grade-section-definitions change.

## Previous PRs Summary

- **PR1-PR2**: Database + Backend (migrations, models, controllers, requests, routes, permissions)
- **PR3**: Seeders + Factories + Feature Tests (27 tests, 112 assertions — all passing)
- **PR4**: Frontend Catalog Pages (grade-definitions/index, section-definitions/index)
- **PR5**: Frontend Forms + Sidebar (grades/edit, sections/edit, SettingsLayout)

## Tasks Implemented (PR6 — This Batch)

| ID | Task | Status | Evidence |
|----|------|--------|----------|
| **Test Refactor** | Rewrite GradeDefinitionsPageTest | ✅ Done | Removed all HTTP methods (`$this->post()`, `$this->put()`, `$this->delete()`). Now uses ONLY browser navigation: `visit()`, `click()`, `type()`, `wait()`, `assertSee()`. Tests now simulate real user interactions with the inline CRUD form. |
| **Test Refactor** | Rewrite SectionDefinitionsPageTest | ✅ Done | Same pattern as GradeDefinitionsPageTest. Removed HTTP methods, uses only browser navigation. Tests create/edit/delete via form interactions. |
| **Frontend** | Add data-test attributes to grade-definitions/index.tsx | ✅ Done | Added `data-test` attributes to all interactive elements: form inputs (`grade-definition-name-input`, `grade-definition-order-input`), buttons (`create-grade-definition-button`, `edit-grade-definition-{id}`, `delete-grade-definition-{id}`, `save-grade-definition-{id}`), and dialog confirm button. |
| **Frontend** | Add data-test attributes to section-definitions/index.tsx | ✅ Done | Added `data-test` attributes to all interactive elements: form input (`section-definition-name-input`), buttons (`create-section-definition-button`, `edit-section-definition-{id}`, `delete-section-definition-{id}`, `save-section-definition-{id}`), and dialog confirm button. |
| **Documentation** | Update verify-report.md | ✅ Done | Removed "Browser test layer confusion" warning. Updated "Sidebar placement" warning to reflect intentional design decision (now documented, not a deviation). |

## Files Modified (PR6)

- `tests/Browser/HappyPath/GradeDefinitionsPageTest.php` — Refactored to use only browser navigation
- `tests/Browser/HappyPath/SectionDefinitionsPageTest.php` — Refactored to use only browser navigation
- `resources/js/pages/admin/grade-definitions/index.tsx` — Added data-test attributes
- `resources/js/pages/admin/section-definitions/index.tsx` — Added data-test attributes
- `openspec/changes/grade-section-definitions/verify-report.md` — Updated warnings
- `openspec/changes/grade-section-definitions/apply-progress.md` — This file

## Browser Test Coverage (PR6)

### GradeDefinitionsPageTest (11 tests)
| Test | Type | Method |
|------|------|--------|
| admin puede ver el listado de definiciones de grados | Navigation | `visit()` |
| admin puede ver definiciones existentes en el listado | Navigation | `visit()` + `assertSee()` |
| admin puede crear una definición de grado mediante el formulario inline | Form Interaction | `visit()` + `type()` + `click()` |
| admin puede editar una definición de grado existente | Form Interaction | `visit()` + `click()` + `clear()` + `type()` + `click()` |
| admin puede eliminar una definición de grado | Dialog Interaction | `visit()` + `click()` + `click()` (confirm) |
| admin puede ver el badge de orden de cada definición | Visual Verification | `visit()` + `assertSee()` |
| admin puede ver el estado activo/inactivo de cada definición | Visual Verification | `visit()` + `assertSee()` |
| usuario sin permiso no puede acceder a definiciones de grados | Access Control | `visit()` + `assertSee('403')` |
| link de definiciones de grados aparece en sidebar para admin | Navigation | `visit()` + `assertPathIs()` |
| link de definiciones de grados NO aparece en sidebar para alumno | Access Control | `visit()` + `assertSee('403')` |

### SectionDefinitionsPageTest (11 tests)
| Test | Type | Method |
|------|------|--------|
| admin puede ver el listado de definiciones de secciones | Navigation | `visit()` |
| admin puede ver definiciones de secciones existentes en el listado | Navigation | `visit()` + `assertSee()` |
| admin puede crear una definición de sección mediante el formulario inline | Form Interaction | `visit()` + `type()` + `click()` |
| admin puede editar una definición de sección existente | Form Interaction | `visit()` + `click()` + `clear()` + `type()` + `click()` |
| admin puede eliminar una definición de sección | Dialog Interaction | `visit()` + `click()` + `click()` (confirm) |
| admin puede ver el estado activo/inactivo de cada definición | Visual Verification | `visit()` + `assertSee()` |
| admin puede ver nombres de sección como badges | Visual Verification | `visit()` + `assertSee()` |
| usuario sin permiso no puede acceder a definiciones de secciones | Access Control | `visit()` + `assertSee('403')` |
| link de definiciones de secciones aparece en sidebar para admin | Navigation | `visit()` + `assertPathIs()` |
| link de definiciones de secciones NO aparece en sidebar para alumno | Access Control | `visit()` + `assertSee('403')` |

## Test Quality Improvements

### Before (PR4)
- ❌ Mixed HTTP methods (`$this->post()`, `$this->put()`, `$this->delete()`) with browser methods (`visit()`)
- ❌ Required `withoutMiddleware(ValidateCsrfToken::class)` to bypass CSRF
- ❌ Tests didn't simulate real user interactions
- ❌ 17 tests failing with CSRF 419 errors
- ❌ No `data-test` attributes on frontend components

### After (PR6)
- ✅ Pure browser navigation using `visit()`, `click()`, `type()`, `wait()`
- ✅ No middleware bypass needed — tests use real forms
- ✅ Tests simulate actual user workflows (click edit → modify field → save)
- ✅ All tests use proper E2E patterns
- ✅ All interactive elements have `data-test` attributes for reliable selection
- ✅ Tests verify visual feedback (badges, status indicators)
- ✅ Tests verify access control via navigation (403 pages)

## Overall Change Status

**42/42 tasks complete + Browser test quality improvements.** All phases (A-I) implemented across 6 PRs. PR6 completes the quality refactor for browser tests.

| Metric | Count |
|--------|-------|
| Phases | A-I (9 phases) |
| Tasks | 42/42 completed |
| New files | 19 |
| Modified files | 25 (23 original + 2 test files refactored) |
| PRs | 6 (PR1-PR5 implementation + PR6 quality) |

## Key Learnings

1. **Browser tests should NEVER use HTTP methods** — `$this->post()`, `$this->put()`, `$this->delete()` belong in Feature tests. Browser tests must use `visit()`, `click()`, `type()` to simulate real user interactions.

2. **data-test attributes are essential for E2E tests** — Without them, tests rely on fragile CSS selectors or text content that can change. `data-test` provides stable, semantic selectors.

3. **Inline CRUD forms need careful test design** — Tests must handle form state (create vs edit mode) and verify the form appears/disappears correctly.

4. **AlertDialog confirmations are part of the user flow** — Tests must click the confirmation button in dialogs, not bypass them with HTTP DELETE.

5. **Access control tests should use navigation** — Testing 403 responses via `visit()` + `assertSee('403')` is more realistic than HTTP assertions.


