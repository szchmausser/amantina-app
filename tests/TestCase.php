<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Override truncateTables to handle SQLite FK constraints.
     *
     * PROBLEM:
     * - Browser tests use DatabaseTruncation which truncates tables between tests
     * - SQLite enforces FK constraints during TRUNCATE, causing errors:
     *   "SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed"
     * - PostgreSQL handles this correctly with TRUNCATE CASCADE
     *
     * SOLUTION:
     * - Disable FK checks in SQLite BEFORE truncation
     * - Call parent truncation logic
     * - Re-enable FK checks in SQLite AFTER truncation
     *
     * WHY THIS WORKS:
     * - DatabaseTruncation trait calls truncateTables() method
     * - By overriding this method, we inject FK handling BEFORE truncation
     * - beforeEach()/afterEach() hooks don't work because they execute AFTER truncation
     *
     * IMPORTANT:
     * - Only affects SQLite (test environment)
     * - PostgreSQL (development/production) maintains normal behavior
     * - FK checks are ALWAYS re-enabled after truncation to maintain integrity
     */
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

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
