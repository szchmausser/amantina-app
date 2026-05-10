# Design: Grade & Section Definitions

## Technical Approach

Replicate the `TermType`/`SchoolTerm` frozen-copy pattern for Grades and Sections. Catalog tables (`grade_definitions`, `section_definitions`) with inline CRUD on index pages. Alter `grades` and `sections` to add nullable FK + frozen name column, backfilled from existing `name`. Frontend Select replaces free-text Input for creation; Select is disabled on edit.

## Architecture Decisions

### Decision: Frozen Copy Pattern (follow SchoolTerm)

| Option | Tradeoff | Decision |
|--------|----------|----------|
| No frozen copy — rely on FK join | Definition rename breaks history | **Frozen copy wins** — `grade_definition_name` / `section_definition_name` stored at creation, updated on definition change but locked on grade edit |
| Hard FK with restrict | Can't soft-delete definitions used by existing grades | **Nullable FK + no FK constraint** — mirror `term_type_id` freedom to allow definition deletion without cascading |

### Decision: Inline CRUD (follow TermType)

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Dedicated form pages | More navigation, extra page files | **Inline on index** — same pattern as term-types: form row at top of list, edit-in-place |
| Index layout only | Cleaner but breaking pattern | **Use SettingsLayout** — all academic CRUD uses it |

### Decision: No FK constraint on definition_id

Following the `term_type_id` pattern: store the ID as a plain `foreignId` but then drop the FK constraint in a follow-up migration (like `remove_term_type_fk_from_school_terms`). This keeps migrations reversible and avoids blocking definition deletions.

## Data Flow

```
GradeDefinition/SectionDefinition CRUD
  ─→ Controller: Gate → validate → create/update/delete → redirect to index

Grade/Section Create
  Form: Select active definitions
  ─→ Request: validates definition_id exists
  ─→ Controller: resolve definition name → store {definition_id, definition_name, ...}

Grade/Section Edit
  Form: Load current definition (disabled Select)
  ─→ Controller: skip definition_id from input (not in form request)
  ─→ Only update other fields (order, etc.)
```

## Database Migrations

3 migration files:

### 1. `create_grade_definitions_table`

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigIncrements | PK |
| name | string(100) | unique |
| order | tinyInteger | default 0 |
| is_active | boolean | default true |
| timestamps | — | — |
| softDeletes | — | — |

### 2. `create_section_definitions_table`

| Column | Type | Constraints |
|--------|------|-------------|
| id | bigIncrements | PK |
| name | string(10) | unique, regex `^[A-Z]$` |
| is_active | boolean | default true |
| timestamps | — | — |
| softDeletes | — | — |

### 3. `add_definition_id_and_name_to_grades_and_sections`

**grades** — add:
- `grade_definition_id` (unsignedBigInteger, nullable, after `academic_year_id`)
- `grade_definition_name` (string(100), nullable, after `grade_definition_id`)
- No FK constraint (like term_type_id pattern)

**sections** — add:
- `section_definition_id` (unsignedBigInteger, nullable, after `grade_id`)
- `section_definition_name` (string(20), nullable, after `section_definition_id`)
- No FK constraint

**Backfill** (PostgreSQL-compatible `UPDATE FROM`):
```sql
UPDATE grades SET grade_definition_name = name WHERE grade_definition_name IS NULL;
UPDATE sections SET section_definition_name = name WHERE section_definition_name IS NULL;
```

**Indexes**:
- `grades.grade_definition_id` — plain index (not unique)
- `sections.section_definition_id` — plain index

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Models/GradeDefinition.php` | Create | Model, fillable: name/order/is_active, casts: boolean+integer |
| `app/Models/SectionDefinition.php` | Create | Model, fillable: name/is_active, casts: boolean |
| `app/Models/Grade.php` | Modify | Add `gradeDefinition()` belongsTo, add `grade_definition_id` + `grade_definition_name` to fillable |
| `app/Models/Section.php` | Modify | Add `sectionDefinition()` belongsTo, add `section_definition_id` + `section_definition_name` to fillable |
| `app/Http/Controllers/Admin/GradeDefinitionController.php` | Create | Full CRUD mirroring TermTypeController, uses `grade_definitions.*` gates |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | Create | Full CRUD mirroring TermTypeController, uses `section_definitions.*` gates |
| `app/Http/Controllers/Admin/GradeController.php` | Modify | Create: resolve definition name from definition_id; Edit: skip definition_id |
| `app/Http/Controllers/Admin/SectionController.php` | Modify | Same pattern as GradeController |
| `app/Http/Requests/Admin/StoreGradeDefinitionRequest.php` | Create | name(required,string,max:100,unique), order(required,int,min:1), is_active(boolean) |
| `app/Http/Requests/Admin/UpdateGradeDefinitionRequest.php` | Create | Same + ignore current ID on unique |
| `app/Http/Requests/Admin/StoreSectionDefinitionRequest.php` | Create | name(required,string,max:1,regex:/^[A-Z]$/,unique), is_active(boolean) |
| `app/Http/Requests/Admin/UpdateSectionDefinitionRequest.php` | Create | Same + ignore |
| `app/Http/Requests/Admin/StoreGradeRequest.php` | Modify | Replace `name` with `grade_definition_id`(required,exists) |
| `app/Http/Requests/Admin/UpdateGradeRequest.php` | Modify | Drop `name` (not editable), keep `order` |
| `app/Http/Requests/Admin/StoreSectionRequest.php` | Modify | Replace `name` with `section_definition_id`(required,exists) |
| `app/Http/Requests/Admin/UpdateSectionRequest.php` | Modify | Drop `name` (not editable) |
| `resources/js/pages/admin/grade-definitions/index.tsx` | Create | Inline CRUD table (name, order, is_active badge, edit/delete) — replicates term-types/index |
| `resources/js/pages/admin/section-definitions/index.tsx` | Create | Inline CRUD table (name, is_active badge, edit/delete) — same pattern without order |
| `resources/js/pages/admin/grades/edit.tsx` | Modify | Replace name Input with Select listing active grade definitions; disabled on edit |
| `resources/js/pages/admin/sections/edit.tsx` | Modify | Replace name Input with Select listing active section definitions; disabled on edit |
| `database/seeders/GradeDefinitionSeeder.php` | Create | "1er Año" through "5to Año", order 1-5 |
| `database/seeders/SectionDefinitionSeeder.php` | Create | "A" through "E" |
| `database/seeders/GradeSeeder.php` | Modify | Set `grade_definition_id` + `grade_definition_name` from definitions |
| `database/seeders/SectionSeeder.php` | Modify | Set `section_definition_id` + `section_definition_name` from definitions |
| `database/seeders/DemoDataSeeder.php` | Modify | Use definitions + factory relationship |
| `database/seeders/CompleteTestDataSeeder.php` | Modify | Run GradeDefinitionSeeder + SectionDefinitionSeeder before GradeSeeder/SectionSeeder |
| `database/seeders/RoleAndPermissionSeeder.php` | Modify | Add 8 new permissions |
| `database/factories/GradeFactory.php` | Modify | Set `grade_definition_id` to random existing definition |
| `database/factories/SectionFactory.php` | Modify | Set `section_definition_id` to random existing definition |
| `resources/js/layouts/settings/layout.tsx` | Modify | Add sidebar links for grade-definitions and section-definitions |
| `routes/web.php` | Modify | Add Route::resource for both definition controllers |

## Interfaces / Contracts

### Wayfinder Routes

```typescript
// New routes generated by Wayfinder:
import { index as gradeDefsIndex } from '@/routes/admin/grade-definitions';
import { index as sectionDefsIndex } from '@/routes/admin/section-definitions';
```

### Props Pattern (grade-definitions/index.tsx)

```typescript
interface GradeDefinition {
    id: number;
    name: string;
    order: number;
    is_active: boolean;
}

interface Props {
    gradeDefinitions: GradeDefinition[];
}
```

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Feature | GradeDefinitionController CRUD | Full test class: index/store/update/destroy + authorization for each gate (following SchoolTermControllerTest pattern) |
| Feature | SectionDefinitionController CRUD | Same pattern |
| Feature | GradeController updated | Update existing tests: submit `grade_definition_id` instead of `name`; verify frozen copy stored; verify edit ignores name |
| Feature | SectionController updated | Same pattern for sections |
| Browser | Grade definitions page | Happy path: create, edit, delete; edge: duplicate name |
| Browser | Section definitions page | Happy path: create, edit, delete; edge: non-letter name rejected |

## Migration / Rollout

No feature flags needed. Run migrations sequentially:
1. `create_grade_definitions_table`
2. `create_section_definitions_table`
3. `add_definition_id_and_name_to_grades_and_sections` (backfills existing data)
4. Seeders populate default definitions
5. Existing tests updated to match new schema

Rollback: reverse migration order.

## Open Questions

None.
