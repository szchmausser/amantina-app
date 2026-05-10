# Proposal: Grade & Section Definitions

## Intent

Replace free-text grade/section names with reusable definition catalogs (mirroring `TermType` → `SchoolTerm`). Prevents typos, enables standardized naming, and preserves history via frozen copies.

## Scope

### In Scope
1. `GradeDefinition` CRUD (model, migration, controller, Form Requests, routes, Inertia pages)
2. `SectionDefinition` CRUD (same pattern, no `order` field needed)
3. Migrate `grades`: add `grade_definition_id` (FK) + `grade_definition_name` (frozen copy)
4. Migrate `sections`: add `section_definition_id` (FK) + `section_definition_name` (frozen copy)
5. Grade form: text Input → Select from active definitions
6. Section form: text Input → Select from active definitions
7. Update seeders (GradeSeeder, SectionSeeder, DemoDataSeeder, CompleteTestDataSeeder)
8. Update factories + tests
9. Sidebar nav links for both catalogs
10. New permissions: `grade_definitions.*`, `section_definitions.*`

### Out of Scope
- Removing old `name` column (kept for migration safety)
- Bulk import/export of definitions
- Multi-institution isolation

## Capabilities

### New
- `grade-definition-catalog`: Admin CRUD for reusable grade templates (name, order, is_active)
- `section-definition-catalog`: Admin CRUD for reusable section templates (name, is_active)

### Modified
- `grade-management`: `name` replaced by Select from definitions + frozen copy
- `section-management`: `name` replaced by Select from definitions + frozen copy

## Approach

Exact `TermType`/`SchoolTerm` replication:
- Definition tables: id, name, order (grade only), is_active, timestamps, softDeletes
- FK nullable with `nullOnDelete`; frozen name copied on create/update in controller
- Form Requests validate FK existence; uniqueness per year+definition
- Frontend: inline CRUD (like TermType), Select in grade/section forms
- Migration backfills existing `name` into frozen column for all rows
- Permission pattern: 4 new permissions per catalog (view/create/edit/delete), admin only

## Affected Areas

| Area | Change |
|------|--------|
| `app/Models/` | New: `GradeDefinition`, `SectionDefinition`. Modified: `Grade`, `Section` |
| `app/Http/Controllers/Admin/` | New: 2 definition controllers. Modified: GradeController, SectionController |
| `app/Http/Requests/Admin/` | Modified: Store/UpdateGrade/SectionRequest (replace name) |
| `database/migrations/` | 4 new migrations (2 create + 2 alter) |
| `database/seeders/` | Modified: RoleAndPermissionSeeder, GradeSeeder, SectionSeeder, Demo, CompleteTestData |
| `database/factories/` | Modified: GradeFactory, SectionFactory |
| `resources/js/pages/admin/` | New: grade-definitions, section-definitions. Modified: grades/edit, sections/edit |
| `resources/js/layouts/settings/layout.tsx` | Add nav links |
| `routes/web.php` | Add resource routes |
| `tests/Feature/Admin/` | 2 new + 2 modified test files |

## Risks

| Risk | Mitigation |
|------|------------|
| Null frozen copies on existing rows | Migration backfills `grade_definition_name` = current `name` |
| Unique constraint breaks | Change to use `definition_id` instead of `name` |
| 8 new permissions added | Same pattern as existing catalogs |

## Rollback Plan

1. Run migration `down()` in reverse order (drop FK columns → drop definition tables)
2. Revert controllers, requests, routes, frontend, seeders, factories
3. Restore `name` validation in Form Requests

## Dependencies

- Wayfinder: re-generate route types after adding new routes
- RoleAndPermissionSeeder: must be re-seeded

## Success Criteria

- [ ] Grade form uses Select from definitions; frozen name stored and persists after definition rename
- [ ] Section form uses Select from definitions; frozen name stored
- [ ] All existing tests pass (modified + new pass)
- [ ] Sidebar has both definition catalog links
