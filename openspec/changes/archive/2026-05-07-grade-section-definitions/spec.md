# Delta: Grade & Section Definitions

## Grade Definition Catalog (NEW)

### Purpose

Reusable catalog of grade templates (e.g. "1er Año", "2do Año"), mirroring the TermType pattern. Replaces free-text grade names with curated definitions.

### Requirements

#### R-GD-01: List Grade Definitions

The system MUST display all grade definitions ordered by the `order` column. Only users with `grade_definitions.view` SHALL access this page.

#### Scenario: Admin views grade definitions

- GIVEN the user has the `grade_definitions.view` permission
- WHEN they navigate to `/admin/grade-definitions`
- THEN they see a list of all grade definitions sorted by `order`
- AND each row shows `name`, `order`, and `is_active` status

#### R-GD-02: Create Grade Definition

The system MUST allow creation of grade definitions with `name` (unique), `order` (integer, default 0), and `is_active` (boolean, default true). Only users with `grade_definitions.create` SHALL create definitions.

#### Scenario: Admin creates a grade definition

- GIVEN the user has `grade_definitions.create` permission
- WHEN they submit name "1er Año", order 1, is_active true
- THEN the definition is created and shown in the list

#### Scenario: Duplicate name is rejected

- GIVEN a definition "1er Año" already exists
- WHEN the user creates another with the same name
- THEN they receive a validation error on the name field

#### R-GD-03: Update Grade Definition

The system MUST allow updating `name`, `order`, and `is_active` of existing definitions. Only users with `grade_definitions.edit` SHALL update.

#### Scenario: Admin edits the order

- GIVEN a definition exists with order 1
- WHEN the user changes its order to 2
- THEN the update succeeds and the list reflects the new order

R-GD-04: Delete Grade Definition
The system MUST soft-delete grade definitions. Only users with `grade_definitions.delete` SHALL delete. Deletion is allowed regardless of whether grades reference it (nullable FK preserves data).

#### Scenario: Admin soft-deletes a definition

- GIVEN a definition exists with no grades referencing it
- WHEN the user deletes it
- THEN the definition disappears from the list and `deleted_at` is set

#### R-GD-05: Sidebar Navigation

The system MUST include a "Definiciones de Grados" link in the admin sidebar under the "Definiciones" section (alongside term-type definitions), visible only to users with `grade_definitions.view`.

---

## Section Definition Catalog (NEW)

Mirrors GradeDefinition but without the `order` column. Same patterns for CRUD, permissions (`section_definitions.*`), sidebar link ("Definiciones de Secciones"), and soft-deletes.

#### R-SD-01: List / Create / Update / Delete Section Definitions

Same requirements as R-GD-01 through R-GD-04, with these differences:

- No `order` column
- Name unique constraint applies to `section_definitions.name`
- Validation: `max:20` for name, regex `/^[A-Z]$/` (single uppercase letter)

#### Scenario: Admin creates "A" section definition

- GIVEN the user has `section_definitions.create`
- WHEN they submit name "A"
- THEN the definition is created

#### Scenario: Non-letter name is rejected

- GIVEN the user creates a section definition
- WHEN they submit name "AA" or "1" or "Abc"
- THEN they receive a validation error

---

## Grade Management (MODIFIED)

#### R-GM-01: Grade creation uses definition (previously: free-text name)

The system MUST replace the free-text `name` input with a `<Select>` component listing active `grade_definitions` ordered by `order`. On create/store, the controller MUST:

1. Find the selected `GradeDefinition`
2. Copy its `name` into `grade_definition_name` (frozen copy)
3. Store `grade_definition_id`

#### Scenario: Admin creates a grade from a definition

- GIVEN the user is on the grade creation form
- WHEN they select "1er Año" from the definition dropdown, set order to 1, and submit
- THEN a Grade is created with `grade_definition_id` = the selected definition's ID, `grade_definition_name` = "1er Año", and `order` = 1

#### R-GM-02: Grade editing locks the definition (previously: name editable)

The system MUST disable the `grade_definition_id` select in edit mode. Only `order` SHALL be editable. The frozen `grade_definition_name` MUST NOT change on update.

#### Scenario: Admin edits grade order

- GIVEN an existing grade with definition "1er Año" and order 1
- WHEN the user submits order=2
- THEN the order updates to 2, grade_definition_id stays unchanged, grade_definition_name stays "1er Año"

#### R-GM-03: Migration backfills existing names

The migration adding `grade_definition_id` and `grade_definition_name` MUST backfill: for all rows where `grade_definition_name IS NULL`, set `grade_definition_name = name`.

#### Scenario: Existing grades retain their name after migration

- GIVEN there are 3 grades with names "1er Año", "2do Año", "3er Año"
- WHEN the migration runs
- THEN each grade gets `grade_definition_name` equal to its current `name`

---

## Section Management (MODIFIED)

Same pattern as Grade Management (R-GM-01 through R-GM-03), adapted for sections:

#### R-SM-01: Section creation uses definition

- GIVEN the user is on the section creation form
- WHEN they select "A" from the definition dropdown
- THEN a Section is created with `section_definition_id` = the selected definition's ID, `section_definition_name` = "A"

#### R-SM-02: Section editing locks the definition

- GIVEN an existing section
- WHEN the user edits it
- THEN `section_definition_id` is disabled; only grade_id/academic_year_id remain editable

#### R-SM-03: Backfill existing sections

- GIVEN existing sections with names "Sección A", "Sección B"
- WHEN the migration runs
- THEN each gets `section_definition_name` = its current `name`

---

## Seeders & Factories (ADDED)

#### R-SF-01: Default definitions seeded

The system SHALL include `GradeDefinitionSeeder` ("1er Año" through "5to Año") and `SectionDefinitionSeeder` ("A" through "E").

#### R-SF-02: Factories reference definitions

`GradeFactory` MUST set `grade_definition_id` to a random existing definition. `SectionFactory` MUST set `section_definition_id` to a random existing definition.

#### Scenario: Factory creates grade with valid definition

- GIVEN at least one grade definition exists
- WHEN `GradeFactory::create()` is called
- THEN the grade has a non-null `grade_definition_id` pointing to an existing definition

---

## Permissions (ADDED)

| Permission                   | Scope                        |
| ---------------------------- | ---------------------------- |
| `grade_definitions.view`     | View grade definition list   |
| `grade_definitions.create`   | Create grade definitions     |
| `grade_definitions.edit`     | Edit grade definitions       |
| `grade_definitions.delete`   | Delete grade definitions     |
| `section_definitions.view`   | View section definition list |
| `section_definitions.create` | Create section definitions   |
| `section_definitions.edit`   | Edit section definitions     |
| `section_definitions.delete` | Delete section definitions   |

All 8 permissions MUST be granted to the `admin` role only.

---

## Tests (ADDED)

#### R-T-01: Feature tests for definition controllers

The system SHALL include feature tests covering:

- Index renders with definitions
- Store with valid/invalid data
- Update with valid/invalid data
- Destroy soft-deletes
- Authorization (each gate enforced)

#### R-T-02: Updated grade/section tests

Existing grade and section tests MUST be updated to handle the definition relationship, including form submission with `grade_definition_id` / `section_definition_id`.

#### R-T-03: Browser tests for CRUD pages

The system SHALL include browser tests for the new definition CRUD pages covering happy path (create, edit, delete) and edge cases (duplicate name, unauthorized access).
