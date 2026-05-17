---
name: test-database-isolation
description: Database isolation patterns for Laravel + Pest tests. Activates when writing, debugging, or fixing tests that fail in suite but pass in isolation, or when dealing with DatabaseTruncation, RefreshDatabase, FK constraints, or residual data contamination.
model: openrouter/deepseek/deepseek-v4-flash:free
---

# Test Database Isolation

## Trigger

Activate this skill when:
- A test passes in isolation but fails in the full suite
- Debugging database contamination between tests
- Writing new browser tests or feature tests
- Encountering `FOREIGN KEY constraint failed` during test truncation
- Adding new entities with FK relationships to the test suite
- Reviewing or refactoring existing test isolation patterns

## Root Cause: DatabaseTruncation Without CASCADE

### The Problem

Laravel's `DatabaseTruncation` trait truncates tables **one-by-one without `CASCADE`**:

```php
// Internally, DatabaseTruncation does this:
$table->truncate();  // ❌ Fails silently if FK references exist
```

In PostgreSQL with complex foreign keys (especially `spatie/laravel-permission` pivot tables like `model_has_roles`, `model_has_permissions`, `role_has_permissions`), this causes:

1. **Silent data retention**: Tables with FK references are NOT truncated because PostgreSQL rejects the operation
2. **Residual data between tests**: The next test starts with orphaned rows from the previous test
3. **Non-deterministic failures**: Tests pass in isolation (clean DB) but fail in suite (contaminated state)

### Why RefreshDatabase Doesn't Work for Browser Tests

`RefreshDatabase` uses database transactions for isolation. Browser tests (Playwright/E2E) **cannot use transactions** because:

- The browser opens real HTTP connections that escape the transaction scope
- Each request from the browser is a separate database connection
- Transactions only work when all queries share the same connection

Therefore, browser tests MUST use `DatabaseTruncation`, which requires the CASCADE fix.

## The Solution: Double-Guard Truncation

### Implementation (Already in `tests/TestCase.php`)

```php
protected function forceTruncateAllTables(): void
{
    $driver = DB::getDriverName();

    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF');
        // ... truncate each table individually ...
        DB::statement('PRAGMA foreign_keys = ON');
    } else {
        // PostgreSQL: single statement with CASCADE
        $tableNames = collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn ($name) => in_array($name, ['migrations']))
            ->map(fn ($name) => '"'.$name.'"')
            ->implode(', ');

        DB::statement("TRUNCATE TABLE {$tableNames} CASCADE");
    }
}
```

### Double-Guard Pattern

| Hook | When | Purpose |
|------|------|---------|
| `setUp()` | Before each test | Guarantees clean slate regardless of what previous test left |
| `tearDown()` | After each test | Cleans up own residuals, doesn't leave garbage for next test |

This ensures **complete test isolation**: each test is truly agnostic of others.

### Scope

Only applies to browser tests (`DatabaseTruncation`). Feature tests (`RefreshDatabase`) are unaffected because they use transactions/migrate:fresh.

## Rules for Writing Tests

### Rule 1: Each Test Creates Its Own Data

```php
// ❌ BAD — assumes another test created this data
it('shows user profile', function () {
    $this->actingAs(User::first())->browse(function ($browser) {
        // Who is User::first()? Did another test create it?
    });
});

// ✅ GOOD — test creates its own data
it('shows user profile', function () {
    $user = User::factory()->create();
    $this->seed(RoleAndPermissionSeeder::class);
    $user->assignRole('admin');

    $this->actingAs($user)->browse(function ($browser) use ($user) {
        $browser->visit('/profile')
                ->assertSee($user->name);
    });
});
```

### Rule 2: Seeders Run Inside the Test, Not Globally

```php
// ❌ BAD — global seed in Pest.php that runs for ALL tests
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class); // Runs even for tests that don't need it
});

// ✅ GOOD — seed only in tests that need it
beforeEach(function () {
    // Only seed if this specific test file needs it
});

// Or in the test itself:
it('does something with roles', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    // ...
});
```

### Rule 3: Use `beforeEach` for Shared Setup Within a File

```php
// tests/Browser/AdminUserTest.php
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create()->assignRole('admin');
});

it('can create users', function () {
    $this->actingAs($this->admin)->browse(function ($browser) {
        // ...
    });
});

it('can delete users', function () {
    $this->actingAs($this->admin)->browse(function ($browser) {
        // ...
    });
});
```

Each test still gets a fresh database because `forceTruncateAllTables()` runs in `setUp()`.

### Rule 4: Never Rely on Test Execution Order

```php
// ❌ BAD — test A creates data that test B depends on
it('creates a user', function () { /* ... */ });
it('edits that user', function () {
    // Assumes the user from the previous test exists
});

// ✅ GOOD — each test is self-contained
it('creates a user', function () {
    $user = User::factory()->create();
    // ...
});

it('edits an existing user', function () {
    $user = User::factory()->create(); // Creates its own user
    // ...
});
```

### Rule 5: Verify Database State After Critical Operations

For **feature tests**, verify the database:

```php
it('creates a user in the database', function () {
    $this->post(route('users.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});
```

For **browser tests**, verify what the user sees (NOT `assertDatabaseHas`):

```php
it('creates a user and shows it in the list', function () {
    $this->actingAs($this->admin)->browse(function ($browser) {
        $browser->visit(route('users.create'))
                ->type('[name="name"]', 'Test User')
                ->type('[name="email"]', 'test@example.com')
                ->press('Guardar')
                ->waitForText('Test User')       // Verify in UI
                ->assertSee('test@example.com'); // Verify in UI
    });
});
```

## Debugging Contaminated Tests

### Step 1: Check Database State After Test Run

```bash
php artisan tinker --env=testing --execute="
echo 'Users: '.App\Models\User::count().
     ' Roles: '.Spatie\Permission\Models\Role::count().
     ' ModelHasRoles: '.DB::table('model_has_roles')->count().
     ' Media: '.Spatie\MediaLibrary\MediaCollections\Models\Media::count();
"
```

If counts are > 0 after tests complete, data is leaking.

### Step 2: Run the Failing Test in Isolation

```bash
php artisan config:clear; php artisan cache:clear
php artisan test --env=testing --compact tests/Browser/SpecificTest.php
```

If it passes alone but fails in suite → contamination confirmed.

### Step 3: Check for Missing Seeders

Common missing seeders in browser tests:
- `RoleAndPermissionSeeder` — required before assigning roles
- `TermTypeSeeder` — required for academic term operations
- `FieldSessionStatusSeeder` — required for field session operations
- `GradeDefinitionSeeder` — required for grade operations
- `SectionDefinitionSeeder` — required for section operations

### Step 4: Verify the Test Creates All Its Dependencies

```php
// Checklist for each browser test:
// [ ] Seeds required data (roles, permissions, etc.)
// [ ] Creates users with factories
// [ ] Creates related entities (grades, sections, etc.)
// [ ] Uses actingAs() for authentication
// [ ] Does NOT depend on data from other tests
```

## Key Tables with FK Issues

These tables are most likely to retain residual data due to FK constraints:

| Table | FK References | Common Issue |
|-------|--------------|--------------|
| `model_has_roles` | `users`, `roles` | Orphaned role assignments |
| `model_has_permissions` | `users`, `permissions` | Orphaned permission grants |
| `role_has_permissions` | `roles`, `permissions` | Orphaned role-permission links |
| `media` | Various model types | Orphaned file records |
| `student_representatives` | `users` (student, representative) | Orphaned relationships |
| `enrollments` | `users`, `grades`, `sections` | Orphaned enrollments |
| `teacher_assignments` | `users`, `grades`, `sections` | Orphaned assignments |

## What NOT to Do

```php
// ❌ DON'T use DoctrineSchemaManager (deprecated in Laravel 11+)
$tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();

// ❌ DON'T rely on Pest afterEach for database cleanup
// (Pest hooks can lose context; TestCase tearDown is more reliable)
afterEach(function () {
    DB::statement('TRUNCATE...'); // May fail if container is destroyed
});

// ❌ DON'T use RefreshDatabase for browser tests
// (Transactions don't work across HTTP connections)
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser'); // ❌ Will cause data inconsistency

// ❌ DON'T skip seeders assuming data exists
$user = User::where('email', 'admin@test.com')->first(); // May not exist!
```

## Summary

| Principle | Implementation |
|-----------|---------------|
| Each test is self-contained | Creates its own data with factories |
| Clean before test | `setUp()` calls `forceTruncateAllTables()` |
| Clean after test | `tearDown()` calls `forceTruncateAllTables()` |
| Use CASCADE for PostgreSQL | Single `TRUNCATE TABLE ... CASCADE` statement |
| Handle SQLite separately | Disable FK → truncate → re-enable FK |
| Only for browser tests | Check `class_uses_recursive` for `DatabaseTruncation` |
| Feature tests unaffected | `RefreshDatabase` uses transactions |
