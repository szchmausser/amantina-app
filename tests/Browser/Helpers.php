<?php

use App\Models\AcademicYear;

/**
 * Generate a unique academic year name in the format "YYYY-YYYY+1".
 * Queries the database to find the highest year already used and returns the next one.
 * Examples: "2022-2023", "2023-2024", "2024-2025", "2025-2026"
 *
 * @return string Academic year name with full 4-digit years
 */
if (! function_exists('generateUniqueAcademicYearName')) {
    function generateUniqueAcademicYearName(): string
    {
        $baseYear = 2022;

        // Find the highest start year already in the database
        $existingNames = AcademicYear::withTrashed()
            ->pluck('name')
            ->filter(fn ($name) => preg_match('/^\d{4}-\d{4}$/', $name))
            ->map(fn ($name) => (int) explode('-', $name)[0])
            ->filter(fn ($year) => $year >= $baseYear)
            ->sort()
            ->values();

        $nextYear = $existingNames->isEmpty()
            ? $baseYear
            : $existingNames->last() + 1;

        return sprintf('%d-%d', $nextYear, $nextYear + 1);
    }
}
