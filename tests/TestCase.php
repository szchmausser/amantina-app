<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Force truncate ALL application tables with CASCADE.
     *
     * This is the core cleanup mechanism that DatabaseTruncation should do
     * but doesn't — it truncates tables one-by-one without CASCADE, leaving
     * orphaned rows in FK-related tables (spatie/permission pivots, media, etc.).
     *
     * Works for both SQLite (test env) and PostgreSQL (dev/prod).
     */
    protected function forceTruncateAllTables(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            $tables = Schema::getTables();
            $except = ['migrations'];
            foreach ($tables as $table) {
                $name = $table['name'];
                if (! in_array($name, $except)) {
                    try {
                        DB::table($name)->truncate();
                    } catch (\Exception $e) {
                        // Ignore errors (some tables may have issues even with FK disabled)
                    }
                }
            }

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // PostgreSQL: TRUNCATE all tables with CASCADE in a single statement
            $tables = Schema::getTables();
            $except = ['migrations'];
            $tableNames = collect($tables)
                ->pluck('name')
                ->reject(fn ($name) => in_array($name, $except))
                ->map(fn ($name) => '"'.$name.'"')
                ->implode(', ');

            if (! empty($tableNames)) {
                DB::statement("TRUNCATE TABLE {$tableNames} CASCADE");
            }
        }
    }

    /**
     * Override truncateTables to handle SQLite FK constraints.
     *
     * DatabaseTruncation calls this method. We inject FK handling for SQLite
     * before delegating to the parent truncation logic.
     */
    protected function truncateTables(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        parent::truncateTables();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * setUp: Run BEFORE each test.
     *
     * For browser tests (DatabaseTruncation): force truncate ALL tables BEFORE
     * the test runs, ensuring a completely clean slate regardless of what the
     * previous test left behind. DatabaseTruncation's own truncation + seeding
     * happens as part of parent::setUp(), but we run our force truncate first
     * as an extra safety layer.
     *
     * For feature tests (RefreshDatabase): no action needed — RefreshDatabase
     * handles isolation via transactions or migrate:fresh.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Force clean BEFORE the test runs (for browser tests only)
        if (in_array(\Illuminate\Foundation\Testing\DatabaseTruncation::class, class_uses_recursive($this))) {
            $this->forceTruncateAllTables();
        }
    }

    /**
     * tearDown: Run AFTER each test.
     *
     * For browser tests (DatabaseTruncation): force truncate ALL tables AFTER
     * the test completes, ensuring no residual data contaminates the next test.
     *
     * This is the "clean up after yourself" part of test isolation.
     *
     * IMPORTANT: Must run BEFORE parent::tearDown() because the parent destroys
     * the Laravel container, making DB/Schema unavailable.
     */
    protected function tearDown(): void
    {
        // Force clean AFTER the test runs (for browser tests only)
        if (in_array(\Illuminate\Foundation\Testing\DatabaseTruncation::class, class_uses_recursive($this))) {
            $this->forceTruncateAllTables();
        }

        parent::tearDown();
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
