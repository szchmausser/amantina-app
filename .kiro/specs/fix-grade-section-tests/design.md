# Technical Design Document: Fix Grade Section Tests

## Overview

This document outlines the technical approach to fix 81 failing tests after the `grade-section-definitions` feature implementation. The failures stem from 4 systematic issues that require coordinated fixes across Feature tests, Browser tests, and service layer code.

**Goal:** Restore test suite to 738 passing tests (657 current + 81 failing), 0 failures.

---

## Problem Analysis

### Root Causes

#### 1. **Outdated Test Data Creation (Feature Tests)**
- **Root Cause:** Tests create grades/sections using the old schema with free-text `name` field
- **Impact:** ~40-50 Feature tests failing with "The grade definition id field is required"
- **Affected Files:** All Feature tests that use `Grade::create()` or `Section::create()` directly
- **Why it happened:** The feature changed the schema to require `grade_definition_id` and `section_definition_id`, but tests weren't updated

#### 2. **SQLite DATE_TRUNC Incompatibility (Service Layer)**
- **Root Cause:** `HourAccumulatorService::getRepresentativeDashboard()` uses PostgreSQL-specific `DATE_TRUNC()` function
- **Impact:** All tests calling representative dashboard fail in SQLite with "no such function: DATE_TRUNC"
- **Affected Files:** `app/Services/HourAccumulatorService.php` (line ~873)
- **Why it happened:** Development uses PostgreSQL, tests use SQLite, no database abstraction for date functions

#### 3. **Dashboard Props Changed (Feature + Browser Tests)**
- **Root Cause:** Admin dashboard controller was refactored, changing prop structure
- **Impact:** ~10-15 tests expecting old props (`globalCompliance`, `sectionRanking`, `termComparison`)
- **Affected Files:** 
  - `tests/Feature/AdminDashboardTest.php`
  - `tests/Browser/*DashboardTest.php`
- **Why it happened:** Controller refactored but tests not updated in sync

#### 4. **Foreign Key Constraints in SQLite (Browser Tests)**
- **Root Cause:** SQLite enforces FK constraints during `TRUNCATE`, PostgreSQL uses `TRUNCATE CASCADE`
- **Impact:** ~15-20 Browser tests fail with "FOREIGN KEY constraint failed" when truncating `academic_years`
- **Affected Files:** All Browser tests using `RefreshDatabase` trait (Pest 4 uses database truncation by default)
- **Why it happened:** SQLite and PostgreSQL handle FK constraints differently during truncation

---

## Technical Solutions

### Solution 1: Update Test Data Creation (Feature Tests)

**Approach:** Create helper methods to generate test data using the new schema.

**Implementation Strategy:**

1. **Create Test Helper Trait** (`tests/Helpers/CreatesGradesAndSections.php`):
   ```php
   trait CreatesGradesAndSections
   {
       protected function createGradeWithDefinition(
           AcademicYear $academicYear,
           string $name = '1er Año',
           int $order = 1
       ): Grade {
           $definition = GradeDefinition::firstOrCreate(['name' => $name]);
           return Grade::create([
               'grade_definition_id' => $definition->id,
               'academic_year_id' => $academicYear->id,
               'order' => $order,
           ]);
       }

       protected function createSectionWithDefinition(
           Grade $grade,
           string $name = 'A'
       ): Section {
           $definition = SectionDefinition::firstOrCreate(['name' => $name]);
           return Section::create([
               'section_definition_id' => $definition->id,
               'grade_id' => $grade->id,
               'academic_year_id' => $grade->academic_year_id,
           ]);
       }
   }
   ```

2. **Update All Feature Tests:**
   - Replace direct `Grade::create(['name' => ...])` with `$this->createGradeWithDefinition()`
   - Replace direct `Section::create(['name' => ...])` with `$this->createSectionWithDefinition()`
   - Use the trait in all affected test files

**Files to Modify:**
- Create: `tests/Helpers/CreatesGradesAndSections.php`
- Update: All Feature test files that create grades/sections (~30-40 files)

**Verification:**
- Run `php artisan test --filter="Grade|Section" --compact` after each batch of updates
- Ensure no "grade definition id field is required" errors

---

### Solution 2: Database-Agnostic Date Truncation (Service Layer)

**Approach:** Detect database driver and use appropriate date truncation syntax.

**Implementation Strategy:**

1. **Create Database Helper Method** in `HourAccumulatorService`:
   ```php
   private function getWeekTruncationExpression(string $column): string
   {
       $driver = DB::getDriverName();
       
       return match ($driver) {
           'pgsql' => "DATE_TRUNC('week', {$column})",
           'sqlite' => "DATE({$column}, 'weekday 0', '-6 days')",
           default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
       };
   }
   ```

2. **Update Query in `getRepresentativeDashboard()`:**
   ```php
   ->select(
       DB::raw($this->getWeekTruncationExpression('field_sessions.start_datetime') . ' as week'),
       DB::raw('COALESCE(SUM(attendance_activities.hours), 0) as hours')
   )
   ```

**Files to Modify:**
- `app/Services/HourAccumulatorService.php` (lines ~870-880)

**Verification:**
- Run `php artisan test --filter="Representative.*Dashboard" --compact`
- Test in both SQLite (tests) and PostgreSQL (development)

**Alternative Considered:** Use Laravel's query builder date methods, but they don't support week truncation natively.

---

### Solution 3: Update Dashboard Test Assertions (Feature + Browser Tests)

**Approach:** Update test assertions to match new dashboard prop structure.

**Implementation Strategy:**

1. **Identify New Props Structure:**
   - Old: `globalCompliance`, `sectionRanking`, `termComparison`
   - New: `totalStudents`, `requiredHours`, `averageHours`, `distribution`, `onTrackStudents`, `topSections`, `concerningSections`, `alerts`

2. **Update Feature Tests:**
   ```php
   // OLD
   $response->assertInertia(fn ($page) => $page
       ->has('globalCompliance')
       ->has('sectionRanking')
       ->has('termComparison')
   );

   // NEW
   $response->assertInertia(fn ($page) => $page
       ->has('totalStudents')
       ->has('requiredHours')
       ->has('averageHours')
       ->has('distribution')
       ->has('onTrackStudents')
       ->has('topSections')
       ->has('concerningSections')
       ->has('alerts')
   );
   ```

3. **Update Browser Tests:**
   - Remove assertions for removed UI elements (e.g., "Ya asignado" text)
   - Add assertions for new UI elements based on new props
   - Update selectors to match current implementation

**Files to Modify:**
- `tests/Feature/AdminDashboardTest.php`
- `tests/Browser/HappyPath/AdminDashboardTest.php` (if exists)
- Any other tests asserting dashboard props

**Verification:**
- Run `php artisan test --filter="Dashboard" --compact`
- Manually verify dashboard UI matches test expectations

---

### Solution 4: SQLite FK-Safe Database Truncation (Browser Tests)

**Approach:** Override `DatabaseTruncation` trait to disable FK checks during truncation in SQLite.

**⚠️ CRITICAL FINDING:** The `beforeEach()`/`afterEach()` hooks in `tests/Pest.php` do NOT work because:
1. `DatabaseTruncation` truncates tables BEFORE `beforeEach()` executes
2. The `PRAGMA foreign_keys = OFF` arrives too late - tables are already being truncated
3. This causes "FOREIGN KEY constraint failed" errors in Browser tests

**Implementation Strategy:**

1. **Override `DatabaseTruncation` in TestCase** (`tests/TestCase.php`):
   ```php
   protected function truncateTables(): void
   {
       $driver = DB::getDriverName();

       if ($driver === 'sqlite') {
           // Disable FK checks BEFORE truncation
           DB::statement('PRAGMA foreign_keys = OFF');
       }

       // Call parent truncation logic
       parent::truncateTables();

       if ($driver === 'sqlite') {
           // Re-enable FK checks AFTER truncation
           DB::statement('PRAGMA foreign_keys = ON');
       }
   }
   ```

2. **Why This Works:**
   - `DatabaseTruncation` trait calls `truncateTables()` method
   - By overriding this method in `TestCase`, we can inject FK handling BEFORE truncation
   - This ensures `PRAGMA foreign_keys = OFF` executes BEFORE tables are truncated
   - PostgreSQL behavior remains unchanged (no FK pragma statements)

3. **Alternative Approaches Considered:**
   - ❌ `beforeEach()`/`afterEach()` hooks in `Pest.php` - **DOES NOT WORK** (hooks execute after truncation)
   - ❌ Custom trait with manual truncation - Too complex, duplicates Laravel's logic
   - ✅ Override `truncateTables()` in `TestCase` - **RECOMMENDED** (simple, effective, Laravel-native)

**Files to Modify:**
- `tests/TestCase.php` - Override `truncateTables()` method

**Verification:**
- Run `php artisan test tests/Browser --compact`
- Ensure no "FOREIGN KEY constraint failed" errors

---

### Solution 5: Update Browser Test Selectors (Browser Tests)

**Approach:** Update Browser tests to use new UI selectors for grade/section definition dropdowns.

**Implementation Strategy:**

1. **Identify Changed Selectors:**
   - Old: `[data-test="grade-name-input"]` (text input)
   - New: `[data-test="grade-definition-select"]` (dropdown)
   - Old: `[data-test="section-name-input"]` (text input)
   - New: `[data-test="section-definition-select"]` (dropdown)

2. **Update Browser Test Interactions:**
   ```php
   // OLD
   $page->type('[data-test="grade-name-input"]', '1er Año');

   // NEW
   $page->select('[data-test="grade-definition-select"]', $gradeDefinitionId);
   // OR if using text selection:
   $page->selectOption('[data-test="grade-definition-select"]', '1er Año');
   ```

3. **Update Date Comparison Logic:**
   ```php
   // OLD
   $page->assertValue('[data-test="date-input"]', '2024-12-20');

   // NEW - normalize format
   $expectedDate = Carbon::parse('2024-12-20')->format('Y-m-d');
   $actualDate = Carbon::parse($page->value('[data-test="date-input"]'))->format('Y-m-d');
   expect($actualDate)->toBe($expectedDate);
   ```

**Files to Modify:**
- All Browser tests that create/edit grades or sections
- Estimated: ~10-15 Browser test files

**Verification:**
- Run each modified Browser test individually
- Ensure UI interactions work correctly

---

## Implementation Order (DAG-Based)

```
┌─────────────────────────────────────────────────────────────┐
│ Phase 1: Foundation (No Dependencies)                       │
├─────────────────────────────────────────────────────────────┤
│ 1.1 Create test helper trait (CreatesGradesAndSections)    │
│ 1.2 Fix SQLite DATE_TRUNC (HourAccumulatorService)         │
│ 1.3 Add SQLite FK handling (tests/Pest.php global setup)   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 2: Feature Tests (Depends on 1.1)                    │
├─────────────────────────────────────────────────────────────┤
│ 2.1 Update Feature tests - Batch 1 (Grade tests)           │
│ 2.2 Update Feature tests - Batch 2 (Section tests)         │
│ 2.3 Update Feature tests - Batch 3 (Dashboard tests)       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 3: Browser Tests (Depends on 1.1, 1.3)               │
├─────────────────────────────────────────────────────────────┤
│ 3.1 Update Browser tests - Batch 1 (Grade CRUD)            │
│ 3.2 Update Browser tests - Batch 2 (Section CRUD)          │
│ 3.3 Update Browser tests - Batch 3 (Dashboard)             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 4: Verification                                       │
├─────────────────────────────────────────────────────────────┤
│ 4.1 Run full test suite                                    │
│ 4.2 Verify 738 passing, 0 failing                          │
└─────────────────────────────────────────────────────────────┘
```

---

## Files to Modify (Complete List)

### New Files
1. `tests/Helpers/CreatesGradesAndSections.php` - Test helper trait

### Modified Files

#### Service Layer
1. `app/Services/HourAccumulatorService.php` - Add database-agnostic date truncation

#### Test Infrastructure
1. `tests/TestCase.php` - Override `truncateTables()` for SQLite FK handling

#### Feature Tests (Estimated ~30-40 files)
- All tests in `tests/Feature/` that create grades or sections
- `tests/Feature/AdminDashboardTest.php` - Update dashboard assertions
- Search pattern: `Grade::create\(['"]name['"]` and `Section::create\(['"]name['"]`

#### Browser Tests (Estimated ~10-15 files)
- All tests in `tests/Browser/` that create/edit grades or sections
- All tests in `tests/Browser/` that interact with dashboards
- Search pattern: `grade-name-input`, `section-name-input`, `globalCompliance`, `sectionRanking`

---

## Testing Strategy

### Unit Testing
- Not applicable (no new business logic, only test fixes)

### Integration Testing
1. **After Phase 1:** Run `php artisan test --filter="Representative.*Dashboard" --compact` to verify SQLite compatibility
2. **After Phase 2:** Run `php artisan test tests/Feature --compact` to verify Feature tests
3. **After Phase 3:** Run `php artisan test tests/Browser --compact` to verify Browser tests

### Regression Testing
1. **Full Suite:** Run `php artisan test --compact` after all phases
2. **Expected Result:** 738 passing, 0 failing
3. **Verify Unchanged Behavior:**
   - Grade/section creation from UI still works
   - Dashboard metrics display correctly
   - All non-affected tests still pass

---

## Risk Assessment

### High Risk
- **SQLite FK handling:** Disabling FK checks could mask real FK issues
  - **Mitigation:** Only disable during truncation, re-enable immediately after
  - **Mitigation:** Run tests in PostgreSQL periodically to catch FK issues

### Medium Risk
- **Date truncation logic:** Different behavior between SQLite and PostgreSQL
  - **Mitigation:** Add explicit tests for week truncation in both databases
  - **Mitigation:** Document the difference in code comments

### Low Risk
- **Test helper trait:** Simple wrapper around existing factories
  - **Mitigation:** Use `firstOrCreate()` to avoid duplicate definition errors

---

## Performance Considerations

- **Test Suite Duration:** No significant impact expected
  - Helper trait adds minimal overhead (one extra query per grade/section)
  - SQLite FK pragma statements are fast
  - Date truncation logic is equivalent performance

- **Development Workflow:** Improved
  - Tests can run in SQLite (faster than PostgreSQL)
  - No need to switch databases for testing

---

## Rollback Plan

If issues arise during implementation:

1. **Phase 1 Issues:** Revert service layer changes, use PostgreSQL for tests temporarily
2. **Phase 2 Issues:** Revert specific test files, fix incrementally
3. **Phase 3 Issues:** Revert Browser test changes, investigate UI changes separately

**Rollback Command:**
```bash
git checkout HEAD -- <affected-files>
```

---

## Success Criteria

1. ✅ All 738 tests passing (657 current + 81 fixed)
2. ✅ 0 failing tests
3. ✅ Tests run successfully in SQLite (test environment)
4. ✅ Application works correctly in PostgreSQL (development/production)
5. ✅ No regression in existing functionality
6. ✅ Test suite duration remains under 20 minutes

---

## Future Improvements

1. **Database Abstraction Layer:** Consider using Laravel's query builder methods for date operations instead of raw SQL
2. **Test Data Builders:** Create fluent test data builders for complex scenarios
3. **Shared Test Fixtures:** Extract common test setup into shared fixtures
4. **CI/CD Integration:** Run tests in both SQLite and PostgreSQL in CI pipeline

---

## References

- Bugfix Requirements: `.kiro/specs/fix-grade-section-tests/bugfix.md`
- Grade Section Definitions Feature: (previous feature implementation)
- Pest 4 Documentation: https://pestphp.com/docs/
- Laravel Testing Documentation: https://laravel.com/docs/12.x/testing
