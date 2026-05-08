# Amantina App - SDD Initialization Context

## Project Overview

The Bitácora Socioproductiva is a web application for tracking practical hours in the Socioproductiva subject for high school students. It replaces manual paper/spreadsheet records with a centralized platform for administrators, teachers, students, and representatives.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.4+)
- **Frontend**: React 19 with TypeScript
- **Integration**: Inertia.js v2 (client-side rendering)
- **Styling**: Tailwind CSS v4
- **Database**: PostgreSQL 18
- **Authentication**: Laravel Fortify (headless)
- **Key Packages**:
    - spatie/laravel-permission (RBAC)
    - spatie/laravel-medialibrary (file management)
    - Askedio/laravel-soft-cascade (soft deletes cascade)
    - laravel/wayfinder (TypeScript route helpers)

## Testing Infrastructure

- **Test Runner**: Pest 4
- **Test Layers**:
    - Unit: ✅ Available (tests/Unit/)
    - Integration: ✅ Available (tests/Feature/ with RefreshDatabase)
    - E2E: ✅ Available (tests/Browser/ with Playwright)
- **Coverage**: ✅ Available (pest --coverage or similar)
- **Quality Tools**:
    - Linter: ✅ Available (eslint via npm run lint)
    - Type checker: ✅ Available (tsc --noEmit via npm run types:check)
    - Formatter: ✅ Available (prettier via npm run format)

## Project Conventions

- **Language**: Code in English, UI text in Spanish
- **Database**: SoftDeletes on transactional tables, central Institution entity (id=1)
- **Frontend**:
    - Pages: resources/js/pages/
    - UI Components: resources/js/components/ui/
    - No external CDN resources (offline-first)
    - Communication via Inertia.js props only
- **Development**:
    - Backend: Laravel Herd (PHP 8.4)
    - Frontend: npm run dev
    - Database: PostgreSQL 18 (development), amantina_app_testing (tests)
- **Git**:
    - Branches: feature/ or fix/
    - Commits: Conventional Commits (English)
    - Pre-test: php artisan config:clear; php artisan cache:clear; php artisan test --env=testing
- **Testing Protocol**:
    - Mandatory cache clearing before tests to avoid data loss
    - Separate test database: amantina_app_testing
- **Security**: No plaintext passwords, strong unique passwords

## Current Status

- ✅ 700 tests passing (342 Feature + 358 Browser)
- ✅ 2462 assertions
- ✅ 0 tests failed
- Duration: ~18 minutes
