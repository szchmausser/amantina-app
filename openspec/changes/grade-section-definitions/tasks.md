# Tasks: Grade & Section Definitions

## Delivery Strategy
- **strategy**: `ask-on-risk` (present options, orchestrator decides)
- **chain_strategy**: `feature-branch-chain`
- **strict_tdd**: enabled (Pest 4, feature+browser layers)

---

## Phase A: Database Migrations (Foundation)

### A1 — Create `grade_definitions` table
- **Description**: Migration creating `grade_definitions` with columns: id, name (string 100, unique), order (tinyInteger, default 0), is_active (boolean, default true), timestamps, softDeletes.
- **Files**: `database/migrations/xxxx_create_grade_definitions_table.php`
- **Dependencies**: None (root)
- **Est.**: ~45 lines new

### A2 — Create `section_definitions` table
- **Description**: Migration creating `section_definitions` with: id, name (string 10, unique), is_active (boolean, default true), timestamps, softDeletes. No `order` column.
- **Files**: `database/migrations/xxxx_create_section_definitions_table.php`
- **Dependencies**: None (root, can run parallel with A1)
- **Est.**: ~40 lines new

### A3 — Add definition_id and definition_name to grades & sections
- **Description**: Single migration adding to `grades`: `grade_definition_id` (unsignedBigInteger, nullable), `grade_definition_name` (string 100, nullable). To `sections`: `section_definition_id` (unsignedBigInteger, nullable), `section_definition_name` (string 20, nullable). No FK constraints (mirrors term_type_id pattern). Add plain indexes on both definition_id columns. PostgreSQL-compatible backfill: `UPDATE grades SET grade_definition_name = name WHERE grade_definition_name IS NULL` (same for sections).
- **Files**: `database/migrations/xxxx_add_definition_id_and_name_to_grades_and_sections.php`
- **Dependencies**: A1, A2
- **Est.**: ~65 lines new

---

## Phase B: Models

### B1 — Create `GradeDefinition` model
- **Description**: Eloquent model with SoftDeletes, fillable: name/order/is_active, casts: is_active(bool), order(int).
- **Files**: `app/Models/GradeDefinition.php`
- **Dependencies**: A1
- **Est.**: ~25 lines new

### B2 — Create `SectionDefinition` model
- **Description**: Eloquent model with SoftDeletes, fillable: name/is_active, casts: is_active(bool).
- **Files**: `app/Models/SectionDefinition.php`
- **Dependencies**: A2
- **Est.**: ~25 lines new

### B3 — Modify `Grade` model
- **Description**: Add `gradeDefinition()` belongsTo relationship. Add `grade_definition_id` and `grade_definition_name` to `$fillable`. Optionally add an accessor for display (mirroring SchoolTerm's `getTermTypeNameAttribute`).
- **Files**: `app/Models/Grade.php` (modify)
- **Dependencies**: A3, B1
- **Est.**: ~12 lines changed

### B4 — Modify `Section` model
- **Description**: Add `sectionDefinition()` belongsTo relationship. Add `section_definition_id` and `section_definition_name` to `$fillable`.
- **Files**: `app/Models/Section.php` (modify)
- **Dependencies**: A3, B2
- **Est.**: ~12 lines changed

---

## Phase C: Form Requests

### C1 — `StoreGradeDefinitionRequest`
- **Description**: Validation: name(required,string,max:100,unique:grade_definitions), order(required,integer,min:1). Authorize: grade_definitions.create.
- **Files**: `app/Http/Requests/Admin/StoreGradeDefinitionRequest.php`
- **Dependencies**: A1
- **Est.**: ~35 lines new

### C2 — `UpdateGradeDefinitionRequest`
- **Description**: Same as C1 + ignore current ID on unique name. Authorize: grade_definitions.edit.
- **Files**: `app/Http/Requests/Admin/UpdateGradeDefinitionRequest.php`
- **Dependencies**: A1
- **Est.**: ~40 lines new

### C3 — `StoreSectionDefinitionRequest`
- **Description**: Validation: name(required,string,max:1,regex:/^[A-Z]$/,unique:section_definitions), is_active(boolean). Custom message for regex. Authorize: section_definitions.create.
- **Files**: `app/Http/Requests/Admin/StoreSectionDefinitionRequest.php`
- **Dependencies**: A2
- **Est.**: ~40 lines new

### C4 — `UpdateSectionDefinitionRequest`
- **Description**: Same as C3 + ignore current ID. Authorize: section_definitions.edit.
- **Files**: `app/Http/Requests/Admin/UpdateSectionDefinitionRequest.php`
- **Dependencies**: A2
- **Est.**: ~45 lines new

### C5 — Modify `StoreGradeRequest`
- **Description**: Replace `name` validation with `grade_definition_id`(required,exists:grade_definitions,id). Keep academic_year_id and order unchanged. In controller, resolve definition name and set both definition_id + definition_name.
- **Files**: `app/Http/Requests/Admin/StoreGradeRequest.php` (modify)
- **Dependencies**: A1, B1
- **Est.**: ~12 lines changed

### C6 — Modify `UpdateGradeRequest`
- **Description**: Remove `name` from rules entirely (definition is locked on edit). Keep academic_year_id and order. Only `order` is editable. Note: definition_id is NOT submitted in the update form — controller does NOT touch it.
- **Files**: `app/Http/Requests/Admin/UpdateGradeRequest.php` (modify)
- **Dependencies**: A3
- **Est.**: ~10 lines changed

### C7 — Modify `StoreSectionRequest`
- **Description**: Replace `name` with `section_definition_id`(required,exists:section_definitions,id). Add custom message for exists validation. In controller, resolve definition name and store both definition_id + definition_name.
- **Files**: `app/Http/Requests/Admin/StoreSectionRequest.php` (modify)
- **Dependencies**: A2, B2
- **Est.**: ~12 lines changed

### C8 — Modify `UpdateSectionRequest`
- **Description**: Remove `name` from rules (locked on edit). Keep academic_year_id and grade_id.
- **Files**: `app/Http/Requests/Admin/UpdateSectionRequest.php` (modify)
- **Dependencies**: A3
- **Est.**: ~10 lines changed

---

## Phase D: Controllers

### D1 — Create `GradeDefinitionController`
- **Description**: Full CRUD mirroring TermTypeController: index (Gate: grade_definitions.view, return all ordered by `order`), store (Gate: grade_definitions.create), update (Gate: grade_definitions.edit), destroy (Gate: grade_definitions.delete). Uses Form Requests C1 & C2.
- **Files**: `app/Http/Controllers/Admin/GradeDefinitionController.php`
- **Dependencies**: B1, C1, C2
- **Est.**: ~70 lines new

### D2 — Create `SectionDefinitionController`
- **Description**: Same pattern as D1 but for SectionDefinition, without `order`. Uses Form Requests C3 & C4.
- **Files**: `app/Http/Controllers/Admin/SectionDefinitionController.php`
- **Dependencies**: B2, C3, C4
- **Est.**: ~65 lines new

### D3 — Modify `GradeController`
- **Description**: In `store()`: find selected GradeDefinition, set both `grade_definition_id` and `grade_definition_name` on the Grade model. In `update()`: do NOT touch definition_id or definition_name (they're locked). Add `gradeDefinitions` prop to `create()` and `edit()` views (list of active definitions ordered).
- **Files**: `app/Http/Controllers/Admin/GradeController.php` (modify)
- **Dependencies**: B1, B3, C5, C6
- **Est.**: ~20 lines changed

### D4 — Modify `SectionController`
- **Description**: Same pattern as D3: in `store()` resolve definition name from selected SectionDefinition. In `update()` skip definition fields. Add `sectionDefinitions` prop to `create()` and `edit()` views.
- **Files**: `app/Http/Controllers/Admin/SectionController.php` (modify)
- **Dependencies**: B2, B4, C7, C8
- **Est.**: ~20 lines changed

---

## Phase E: Routes & Permissions

### E1 — Add resource routes to web.php
- **Description**: Add two `Route::resource` lines: `grade-definitions` (GradeDefinitionController, except create/edit/show), `section-definitions` (SectionDefinitionController, except create/edit/show). Import both controllers. Add Wayfinder comment markers if required by project convention.
- **Files**: `routes/web.php` (modify)
- **Dependencies**: D1, D2
- **Est.**: ~6 lines changed

### E2 — Update RoleAndPermissionSeeder
- **Description**: Add 8 new permissions: grade_definitions.{view,create,edit,delete}, section_definitions.{view,create,edit,delete}. All granted to `admin` role only.
- **Files**: `database/seeders/RoleAndPermissionSeeder.php` (modify)
- **Dependencies**: None (can run early)
- **Est.**: ~10 lines changed

---

## Phase F: Frontend — Definition Catalog Pages

### F1 — Create `grade-definitions/index.tsx` ✅
- **Description**: Inline CRUD page mirroring term-types/index.tsx. Table/list with name, order badge, is_active badge, edit/delete buttons. Create form at top. Delete via AlertDialog. Permissions-gated buttons. Uses SettingsLayout. Import from `@/routes/admin/grade-definitions` (Wayfinder).
- **Files**: `resources/js/pages/admin/grade-definitions/index.tsx`
- **Dependencies**: D1, E1
- **Est.**: ~250 lines new

### F2 — Create `section-definitions/index.tsx` ✅
- **Description**: Same pattern as F1 but without `order` column. Simpler layout. Name is a single uppercase letter displayed in a badge.
- **Files**: `resources/js/pages/admin/section-definitions/index.tsx`
- **Dependencies**: D2, E1
- **Est.**: ~230 lines new

### F3 — Modify SettingsLayout (sidebar)
- **Description**: Add two new conditional sidebar items under "Académico" section: "Definiciones de Grados" (grade_definitions.view gate, GraduationCap or Tag icon) and "Definiciones de Secciones" (section_definitions.view gate, Layers icon). Place them before the "Grados" and "Secciones" items respectively.
- **Files**: `resources/js/layouts/settings/layout.tsx` (modify)
- **Dependencies**: F1, F2 (must know the route URLs)
- **Est.**: ~18 lines changed

---

## Phase G: Frontend — Grade/Section Forms

### G1 — Modify `grades/edit.tsx`
- **Description**: Replace the name `<Input>` with a `<Select>` component listing `gradeDefinitions` (passed as prop). On create: the select is enabled, user picks a definition. On edit: the select is disabled (locked), showing the current frozen name. The select options come from the `gradeDefinitions` prop. Remove the `name` field from useForm; add `grade_definition_id`.
- **Files**: `resources/js/pages/admin/grades/edit.tsx` (modify)
- **Depends on**: D3 (controller provides gradeDefinitions prop), E1
- **Est.**: ~30 lines changed

### G2 — Modify `sections/edit.tsx`
- **Description**: Same pattern as G1. Replace name `<Input>` with `<Select>` for `sectionDefinitions`. Locked on edit. Uses `section_definition_id`.
- **Files**: `resources/js/pages/admin/sections/edit.tsx` (modify)
- **Depends on**: D4 (controller provides sectionDefinitions prop), E1
- **Est.**: ~30 lines changed

---

## Phase H: Seeders & Factories

### H1 — Create `GradeDefinitionSeeder`
- **Description**: Seeds 5 definitions: "1er Año" through "5to Año", order 1-5. Uses `updateOrCreate` by name.
- **Files**: `database/seeders/GradeDefinitionSeeder.php`
- **Dependencies**: B1 (model exists), A1 (table exists)
- **Est.**: ~30 lines new

### H2 — Create `SectionDefinitionSeeder`
- **Description**: Seeds 5 definitions: "A" through "E". Uses `updateOrCreate`.
- **Files**: `database/seeders/SectionDefinitionSeeder.php`
- **Dependencies**: B2, A2
- **Est.**: ~30 lines new

### H3 — Modify `GradeFactory`
- **Description**: Set `grade_definition_id` to a random existing GradeDefinition's id. Use `GradeDefinition::inRandomOrder()->first()?->id` or factory callback. Keep existing faker behavior as fallback.
- **Files**: `database/factories/GradeFactory.php` (modify)
- **Dependencies**: B1, H1
- **Est.**: ~8 lines changed

### H4 — Modify `SectionFactory`
- **Description**: Set `section_definition_id` to a random existing SectionDefinition's id.
- **Files**: `database/factories/SectionFactory.php` (modify)
- **Dependencies**: B2, H2
- **Est.**: ~8 lines changed

### H5 — Modify `GradeSeeder`
- **Description**: After creating/updating each grade, also set `grade_definition_id` and `grade_definition_name` by matching the grade name to a definition name. Or simplify: since definitions now mirror the old hardcoded names, iterate definitions and create grades from them.
- **Files**: `database/seeders/GradeSeeder.php` (modify)
- **Dependencies**: B1, H1
- **Est.**: ~15 lines changed

### H6 — Modify `SectionSeeder`
- **Description**: Similarly, set `section_definition_id` and `section_definition_name` from definitions when creating sections.
- **Files**: `database/seeders/SectionSeeder.php` (modify)
- **Dependencies**: B2, H2
- **Est.**: ~10 lines changed

### H7 — Modify `DemoDataSeeder`
- **Description**: When creating grades/sections inline (fallback path), also set `grade_definition_id`/`section_definition_id` from definitions.
- **Files**: `database/seeders/DemoDataSeeder.php` (modify)
- **Dependencies**: H5, H6
- **Est.**: ~12 lines changed

### H8 — Modify `CompleteTestDataSeeder`
- **Description**: Add `GradeDefinitionSeeder::class` and `SectionDefinitionSeeder::class` calls before `GradeSeeder`/`SectionSeeder` in step 3 (Academics). Bump step counter from "8" to "9" (or adjust as needed).
- **Files**: `database/seeders/CompleteTestDataSeeder.php` (modify)
- **Dependencies**: H1, H2
- **Est.**: ~5 lines changed

### H9 — Modify `DatabaseSeeder`
- **Description**: Add GradeDefinitionSeeder and SectionDefinitionSeeder calls before GradeSeeder/SectionSeeder.
- **Files**: `database/seeders/DatabaseSeeder.php` (modify)
- **Dependencies**: H1, H2
- **Est.**: ~4 lines changed

---

## Phase I: Tests

### I1 — Create Feature Test: `GradeDefinitionControllerTest`
- **Description**: Full test class following existing test patterns (RefreshDatabase, withoutMiddleware CSRF, seed RoleAndPermissionSeeder). Tests:
  - Admin can view index
  - Admin can create definition (name, order, is_active)
  - Duplicate name validation
  - Admin can update definition
  - Admin can soft-delete definition
  - Non-admin gets 403
  - Each gate enforced (view, create, edit, delete)
- **Files**: `tests/Feature/Admin/GradeDefinitionControllerTest.php`
- **Dependencies**: D1, C1, C2, H1
- **Est.**: ~130 lines new

### I2 — Create Feature Test: `SectionDefinitionControllerTest`
- **Description**: Same pattern as I1 but for sections. Key edge: invalid name format (non-letter) is rejected, name max:1 enforced.
- **Files**: `tests/Feature/Admin/SectionDefinitionControllerTest.php`
- **Dependencies**: D2, C3, C4, H2
- **Est.**: ~130 lines new

### I3 — Modify Feature Test: `GradeControllerTest`
- **Description**: Update all existing grade tests:
  - `test_admin_can_create_grade`: submit `grade_definition_id` instead of `name`, assert `grade_definition_name` is stored as frozen copy
  - `test_cannot_create_grade_with_duplicate_name_in_same_year`: now tests definition-based validation or remove (since name is gone)
  - `test_admin_can_update_grade`: remove `name` from PUT body, assert `grade_definition_name` unchanged
  - Add new test: `test_grade_edit_does_not_change_definition`
  - Seed GradeDefinition before tests
- **Files**: `tests/Feature/Admin/GradeControllerTest.php` (modify)
- **Dependencies**: C5, C6, D3, I1 (needs GradeDefinition model)
- **Est.**: ~35 lines changed

### I4 — Modify Feature Test: `SectionControllerTest`
- **Description**: Same pattern as I3 for sections. Update create/update/delete tests. Add frozen-copy and locked-definition tests. Seed SectionDefinition.
- **Files**: `tests/Feature/Admin/SectionControllerTest.php` (modify)
- **Dependencies**: C7, C8, D4, I2
- **Est.**: ~35 lines changed

### I5 — Create Browser Test: `GradeDefinitionsPageTest` ✅
- **Description**: Browser/E2E test in tests/Browser/HappyPath/. Tests:
  - Happy path: navigate to page, create "1er Año", edit name, delete
  - Edge: duplicate name rejected
  - Unauthorized access blocked (student sees 403)
  - Verify sidebar link exists for admin, hidden for student
- **Files**: `tests/Browser/HappyPath/GradeDefinitionsPageTest.php`
- **Dependencies**: F1, E2, H1
- **Est.**: ~110 lines new

### I6 — Create Browser Test: `SectionDefinitionsPageTest` ✅
- **Description**: Same pattern as I5 for section definitions. Key edge: non-letter name ("AA", "1", "Abc") rejected.
- **Files**: `tests/Browser/HappyPath/SectionDefinitionsPageTest.php`
- **Dependencies**: F2, E2, H2
- **Est.**: ~110 lines new

### I7 — Modify Browser Test: `AcademicStructureTest`
- **Description**: Update existing grade/section creation in browser tests to use definitions. Seed GradeDefinitionSeeder/SectionDefinitionSeeder in beforeEach.
- **Files**: `tests/Browser/HappyPath/AcademicStructureTest.php` (modify)
- **Dependencies**: H1, H2, G1, G2
- **Est.**: ~15 lines changed

---

## Task Summary

| # | ID | Title | Type | Files | Est. Lines | Deps |
|---|----|-------|------|-------|-----------|------|
| 1 | A1 | Create grade_definitions migration | new | 1 | ~45 | — |
| 2 | A2 | Create section_definitions migration | new | 1 | ~40 | — |
| 3 | A3 | Add definition columns to grades/sections | new | 1 | ~65 | A1, A2 |
| 4 | B1 | Create GradeDefinition model | new | 1 | ~25 | A1 |
| 5 | B2 | Create SectionDefinition model | new | 1 | ~25 | A2 |
| 6 | B3 | Modify Grade model | mod | 1 | ~12 | A3, B1 |
| 7 | B4 | Modify Section model | mod | 1 | ~12 | A3, B2 |
| 8 | C1 | Create StoreGradeDefinitionRequest | new | 1 | ~35 | A1 |
| 9 | C2 | Create UpdateGradeDefinitionRequest | new | 1 | ~40 | A1 |
| 10 | C3 | Create StoreSectionDefinitionRequest | new | 1 | ~40 | A2 |
| 11 | C4 | Create UpdateSectionDefinitionRequest | new | 1 | ~45 | A2 |
| 12 | C5 | Modify StoreGradeRequest | mod | 1 | ~12 | A1, B1 |
| 13 | C6 | Modify UpdateGradeRequest | mod | 1 | ~10 | A3 |
| 14 | C7 | Modify StoreSectionRequest | mod | 1 | ~12 | A2, B2 |
| 15 | C8 | Modify UpdateSectionRequest | mod | 1 | ~10 | A3 |
| 16 | D1 | Create GradeDefinitionController | new | 1 | ~70 | B1, C1, C2 |
| 17 | D2 | Create SectionDefinitionController | new | 1 | ~65 | B2, C3, C4 |
| 18 | D3 | Modify GradeController | mod | 1 | ~20 | B1, B3, C5, C6 |
| 19 | D4 | Modify SectionController | mod | 1 | ~20 | B2, B4, C7, C8 |
| 20 | E1 | Add resource routes | mod | 1 | ~6 | D1, D2 |
| 21 | E2 | Update permissions seeder | mod | 1 | ~10 | — |
| 22 | F1 | Create grade-definitions index page | new | 1 | ~250 | D1, E1 |
| 23 | F2 | Create section-definitions index page | new | 1 | ~230 | D2, E1 |
| 24 | F3 | Modify SettingsLayout sidebar | mod | 1 | ~18 | F1, F2 |
| 25 | G1 | Modify grades/edit.tsx | mod | 1 | ~30 | D3, E1 |
| 26 | G2 | Modify sections/edit.tsx | mod | 1 | ~30 | D4, E1 |
| 27 | H1 | Create GradeDefinitionSeeder | new | 1 | ~30 | B1, A1 |
| 28 | H2 | Create SectionDefinitionSeeder | new | 1 | ~30 | B2, A2 |
| 29 | H3 | Modify GradeFactory | mod | 1 | ~8 | B1, H1 |
| 30 | H4 | Modify SectionFactory | mod | 1 | ~8 | B2, H2 |
| 31 | H5 | Modify GradeSeeder | mod | 1 | ~15 | B1, H1 |
| 32 | H6 | Modify SectionSeeder | mod | 1 | ~10 | B2, H2 |
| 33 | H7 | Modify DemoDataSeeder | mod | 1 | ~12 | H5, H6 |
| 34 | H8 | Modify CompleteTestDataSeeder | mod | 1 | ~5 | H1, H2 |
| 35 | H9 | Modify DatabaseSeeder | mod | 1 | ~4 | H1, H2 |
| 36 | I1 | GradeDefinitionControllerTest (Feature) | new | 1 | ~130 | D1, C1, C2, H1 |
| 37 | I2 | SectionDefinitionControllerTest (Feature) | new | 1 | ~130 | D2, C3, C4, H2 |
| 38 | I3 | Modify GradeControllerTest | mod | 1 | ~35 | C5, C6, D3, I1 |
| 39 | I4 | Modify SectionControllerTest | mod | 1 | ~35 | C7, C8, D4, I2 |
| 40 | I5 | GradeDefinitionsPageTest (Browser) | new | 1 | ~110 | F1, E2, H1 |
| 41 | I6 | SectionDefinitionsPageTest (Browser) | new | 1 | ~110 | F2, E2, H2 |
| 42 | I7 | Modify AcademicStructureTest (Browser) | mod | 1 | ~15 | H1, H2, G1, G2 |

---

## Summary Statistics

| Metric | New Files | Modified Files | Total Files | Est. Lines |
|--------|-----------|---------------|-------------|-----------|
| Migrations | 3 | 0 | 3 | ~150 |
| Models | 2 | 2 | 4 | ~74 |
| Form Requests | 4 | 4 | 8 | ~204 |
| Controllers | 2 | 2 | 4 | ~175 |
| Routes | 0 | 1 | 1 | ~6 |
| Permissions | 0 | 1 | 1 | ~10 |
| Frontend Pages | 2 | 2 | 4 | ~540 |
| Settings Layout | 0 | 1 | 1 | ~18 |
| Seeders | 2 | 5 | 7 | ~101 |
| Factories | 0 | 2 | 2 | ~16 |
| Feature Tests | 2 | 2 | 4 | ~330 |
| Browser Tests | 2 | 1 | 3 | ~235 |
| **Total** | **19** | **23** | **42** | **~1,859** |

---

## Dependency Graph (Textual)

```
A1 ───────────────────────────────────────────────────────────────────┐
  ├── B1 ──┬── D1 ── E1 ──┬── F1 ──┬── F3                          │
  │        │               │        │                                │
  │        ├── C1 ─────────┤        │                                │
  │        ├── C2 ─────────┤        │                                │
  │        ├── C5 ──┬── D3 ── G1 ──┤                                │
  │        │        └── I3          │                                │
  │        ├── H1 ──┬── H3 ── H5 ── H7                              │
  │        │        ├── H8 ── H9                                    │
  │        │        └── I1 ── I5 ── I7                              │
  │        └── I5                                                    │
  │                                                                  │
A2 ───────────────────────────────────────────────────────────────────┤
  ├── B2 ──┬── D2 ── E1 ──┬── F2 ──┬── F3                          │
  │        │               │        │                                │
  │        ├── C3 ─────────┤        │                                │
  │        ├── C4 ─────────┤        │                                │
  │        ├── C7 ──┬── D4 ── G2 ──┤                                │
  │        │        └── I4          │                                │
  │        ├── H2 ──┬── H4 ── H6 ── H7                              │
  │        │        ├── H8 ── H9                                    │
  │        │        └── I2 ── I6 ── I7                              │
  │        └── I6                                                    │
  │                                                                  │
A3 ───────────────────────────────────────────────────────────────────┤
  ├── B3 ──┬── D3                                                    │
  ├── B4 ──┬── D4                                                    │
  ├── C6                                                             │
  └── C8                                                             │
                                                                     │
E2 (permissions, independent ─ can run anywhere after A1+A2) ────────┤
  └── I5 ── I6                                                       │
```

---

## Review Workload Forecast

| Metric | Value |
|--------|-------|
| **Total changed lines** | **~1,859** (19 new files + 23 modified files = 42 files) |
| **400-line budget risk** | 🚨 **CRITICAL** — 1,859 lines is **4.6x over budget** |
| **Chained PR recommended?** | **YES — strongly recommended** |
| **Minimum practical chains** | **4-5 chains** (see below) |

### Chained PR Splitting Options

**Option A: Feature slices (recommended) — 5 chains**

| Chain | Tasks | Theme | Est. Lines |
|-------|-------|-------|-----------|
| **PR 1** | A1, A2, A3, E2 | Database foundation + permissions | ~155 |
| **PR 2** | B1, B2, B3, B4, C1-C4, D1, D2, E1 | Definition catalog backend (models, requests, controllers, routes) | ~378 |
| **PR 3** | C5-C8, D3, D4, H1-H9 | Grade/Section frozen-copy integration + seeders | ~213 |
| **PR 4** | F1, F2, F3, G1, G2 | Frontend (definition pages + grade/section form mods + sidebar) | ~558 |
| **PR 5** | I1, I2, I3, I4, I5, I6, I7 | All tests (feature + browser) | ~555 |

**Option B: Vertical slices (PR 1 + PR 2 might be heavy)**

| Chain | Tasks | Theme | Est. Lines |
|-------|-------|-------|-----------|
| **PR 1** | A1, A2, A3, E2 + B1-B4 + C1-C4 + D1-D2 + E1 + H1-H2 | Foundation + definition catalog backend | ~533 |
| **PR 2** | C5-C8 + D3-D4 + H3-H9 + G1-G2 | Grade/Section frozen-copy | ~243 |
| **PR 3** | F1, F2, F3 | Frontend catalog pages | ~498 |
| **PR 4** | All tests | Testing | ~555 |

### Decision Needed Before Apply

1. **Which chaining strategy?** Option A (5 chains, recommended) vs Option B (4 chains)
2. **PR 4 is still ~558 lines** — option to split it further into F1/F2 (catalog pages) and F3/G1/G2 (grade/section forms)
3. **Test ordering**: Do tests run as their own chain (last), or should each feature include its tests in its chain (test-beside-code)?

### Recommended First Task

**A1 — Create `grade_definitions` migration** (root task, zero dependencies)

This is the foundation block. A1 has no dependencies and everything else depends on it. Immediate next step after decision on chaining.
