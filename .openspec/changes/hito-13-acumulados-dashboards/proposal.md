# Change Proposal: Hito 13 — Acumulados y Dashboards

## 1. Intent

Provide role-specific dashboards that visualize accumulated hours, progress toward quotas, and actionable alerts for all four system roles (Admin, Profesor, Estudiante, Representante). Centralize all hour calculations in a single `HourAccumulatorService` so dashboards consume pre-computed data with zero business logic in React components.

## 2. Scope

### In Scope

- **HourAccumulatorService**: Single service for all hour accumulation calculations (jornada + external, traffic light, projections).
- **DashboardController**: Replaces the current placeholder `dashboard.tsx` route with role-aware controller that delegates to `HourAccumulatorService`.
- **Four React dashboard pages**: `dashboard.tsx` (admin), `profesor/dashboard.tsx`, `estudiante/dashboard.tsx`, `representante/dashboard.tsx`.
- **Reusable React components**: Progress bars, stat cards, traffic light badges, alert banners, mini charts.
- **Feature tests**: One per role verifying dashboard renders correct data and access control.

### Out of Scope

- Hito 12 (external hours registration) — the service is designed aditively so `external_hours` defaults to 0.
- Real-time notifications or websockets.
- PDF/Excel report exports (future hito).
- Multi-institution support (single plantel assumption remains).

## 3. Affected Modules

| Module                                           | Impact                                                 |
| ------------------------------------------------ | ------------------------------------------------------ |
| `app/Services/HourAccumulatorService.php`        | **NEW** — Central calculation engine                   |
| `app/Http/Controllers/DashboardController.php`   | **NEW** — Role-aware dashboard controller              |
| `routes/web.php`                                 | **MODIFY** — Replace placeholder route with controller |
| `resources/js/pages/dashboard.tsx`               | **MODIFY** — Admin dashboard (replaces placeholder)    |
| `resources/js/pages/profesor/dashboard.tsx`      | **NEW**                                                |
| `resources/js/pages/estudiante/dashboard.tsx`    | **NEW**                                                |
| `resources/js/pages/representante/dashboard.tsx` | **NEW**                                                |
| `resources/js/components/dashboard/`             | **NEW** — Shared UI components                         |
| `resources/js/types/dashboard.ts`                | **NEW** — TypeScript interfaces                        |
| `tests/Feature/AdminDashboardTest.php`           | **NEW**                                                |
| `tests/Feature/ProfesorDashboardTest.php`        | **NEW**                                                |
| `tests/Feature/EstudianteDashboardTest.php`      | **NEW**                                                |
| `tests/Feature/RepresentanteDashboardTest.php`   | **NEW**                                                |
| `tests/Feature/HourAccumulatorServiceTest.php`   | **NEW**                                                |

## 4. Database Changes

### 4.1 No New Tables Required

All data needed for dashboards already exists in the current schema:

- **Hours**: `attendance_activities.hours` (jornada hours per student per session)
- **Attendance**: `attendances.attended` (who showed up)
- **Sessions**: `field_sessions` (status, base_hours, cancellation_reason, location)
- **Academic structure**: `academic_years`, `school_terms`, `sections`, `enrollments`
- **Health**: `student_health_records`
- **Quotas**: `academic_years.required_hours`

### 4.2 Proposed Database View (Optional, for Performance)

```sql
-- v_student_hour_accumulation
-- Pre-aggregates jornada hours per student per academic year
CREATE MATERIALIZED VIEW v_student_hour_accumulation AS
SELECT
    a.user_id AS student_id,
    a.academic_year_id,
    SUM(aa.hours) AS total_jornada_hours,
    COUNT(DISTINCT a.field_session_id) AS sessions_attended,
    COUNT(DISTINCT CASE WHEN a.attended = true THEN a.field_session_id END) AS sessions_present
FROM attendances a
INNER JOIN attendance_activities aa ON aa.attendance_id = a.id
WHERE a.deleted_at IS NULL AND aa.deleted_at IS NULL
GROUP BY a.user_id, a.academic_year_id;
```

**Decision**: Start WITHOUT the materialized view. If dashboard queries exceed 500ms in production, introduce it as a follow-up. The initial approach uses Eloquent aggregations with proper eager loading.

### 4.3 No Column Additions Needed

The aditive design means `external_hours` = 0 until Hito 12. When Hito 12 arrives, a column `external_hours` will be added to `attendance_activities` or a new `external_hours` table will be created. The service will sum both.

## 5. Service Design: HourAccumulatorService

### 5.1 Location

`app/Services/HourAccumulatorService.php`

### 5.2 Public API

```php
class HourAccumulatorService
{
    /**
     * Total accumulated hours for a student in an academic year.
     * Returns: jornada_hours + external_hours (aditive design).
     */
    public function getStudentTotalHours(User $student, int $academicYearId): float

    /**
     * Breakdown of hours by source (jornada vs external).
     */
    public function getStudentHoursBreakdown(User $student, int $academicYearId): array
    // Returns: ['jornada_hours' => X, 'external_hours' => Y, 'total_hours' => Z]

    /**
     * Traffic light status for a student.
     * Green: >= expected progress
     * Yellow: > 0 but below expected
     * Red: 0 hours or critically below (< 30% at mid-year)
     */
    public function getTrafficLightStatus(User $student, int $academicYearId): string
    // Returns: 'green' | 'yellow' | 'red'

    /**
     * Expected hours at current date based on term progression.
     * Linear interpolation: (days_elapsed / total_days) * required_hours
     */
    public function getExpectedHours(int $academicYearId): float

    /**
     * Progress percentage toward quota.
     */
    public function getProgressPercentage(User $student, int $academicYearId): float

    /**
     * Closure projection: estimated date when student will meet quota
     * based on current accumulation rate.
     */
    public function getProjectedClosureDate(User $student, int $academicYearId): ?Carbon

    /**
     * Hours breakdown by activity category for a student.
     */
    public function getHoursByCategory(User $student, int $academicYearId): Collection

    /**
     * Hours breakdown by location (for admin logistics).
     */
    public function getHoursByLocation(int $academicYearId): Collection

    /**
     * Section ranking by average student progress.
     */
    public function getSectionRanking(int $academicYearId): Collection

    /**
     * Admin: global compliance stats.
     */
    public function getGlobalCompliance(int $academicYearId): array
    // Returns: ['total_students' => N, 'met_quota' => N, 'at_risk' => N, 'on_track' => N]

    /**
     * Admin: alerts — students with zero hours in active term.
     */
    public function getZeroHourAlerts(int $academicYearId): Collection

    /**
     * Admin: sessions without attendance registered.
     */
    public function getSessionsWithoutAttendance(int $academicYearId): Collection

    /**
     * Profesor: pending attendance registrations for their sessions.
     */
    public function getPendingAttendanceForTeacher(User $teacher, int $academicYearId): Collection

    /**
     * Get the active academic year (default filter).
     */
    public function getActiveAcademicYear(): ?AcademicYear
}
```

### 5.3 Internal Design Principles

- **No React business logic**: All calculations happen server-side. React receives plain data arrays.
- **Cached aggressively**: Use `Cache::remember()` with 5-minute TTL for expensive aggregations.
- **Query optimization**: Use `with()` eager loading, `withCount()`, `withSum()`, and database-level aggregations.
- **Testable**: Each public method is independently testable with factory-generated data.

## 6. Controller Design

### 6.1 DashboardController

```php
class DashboardController extends Controller
{
    public function __construct(protected HourAccumulatorService $accumulator) {}

    public function __invoke(Request $request)
    {
        $role = $request->session()->get('active_role');
        $yearId = $request->integer('year') ?? $this->accumulator->getActiveAcademicYear()?->id;

        return match ($role) {
            'admin' => $this->adminDashboard($yearId),
            'profesor' => $this->profesorDashboard($request->user(), $yearId),
            'alumno' => $this->estudianteDashboard($request->user(), $yearId),
            'representante' => $this->representanteDashboard($request->user(), $yearId),
            default => abort(403),
        };
    }
}
```

### 6.2 Route Change

```php
// BEFORE (in routes/web.php):
Route::inertia('dashboard', 'dashboard')->name('dashboard');

// AFTER:
Route::get('dashboard', DashboardController::class)->name('dashboard');
```

### 6.3 Data Payloads per Role

#### Admin

```php
[
    'globalCompliance' => [...],
    'sectionRanking' => [...],
    'hoursByTerm' => [...],
    'sessionStats' => ['completed' => N, 'cancelled' => N, 'cancellationReasons' => [...]],
    'transferredWithPendingHours' => [...],
    'alerts' => ['zeroHourStudents' => [...], 'sessionsWithoutAttendance' => [...]],
    'yearOverYearComparison' => [...],
    'topActivityCategories' => [...],
    'averageHoursPerSession' => float,
    'teacherSessionCounts' => [...],
    'averageAttendanceRate' => float,
    'hoursByLocation' => [...],
    'academicYears' => [...], // for year filter dropdown
    'activeYearId' => int,
]
```

#### Profesor

```php
[
    'students' => [
        ['id' => N, 'name' => str, 'accumulated_hours' => float, 'quota' => float,
         'progress_pct' => float, 'traffic_light' => str, 'recent_health_conditions' => [...]],
    ],
    'ownSessionStats' => ['completed' => N, 'cancelled' => N, 'total_hours' => float],
    'pendingAttendance' => [...],
    'lowAttendanceStudents' => [...],
    'hoursByCategory' => [...],
    'averageHoursPerStudent' => float,
    'sessionsPerTerm' => [...],
    'recentHealthReminders' => [...],
    'academicYears' => [...],
    'activeYearId' => int,
]
```

#### Estudiante

```php
[
    'progress' => ['accumulated' => float, 'quota' => float, 'percentage' => float, 'traffic_light' => str],
    'breakdownByYear' => [...],
    'breakdownByTerm' => [...],
    'sessionHistory' => [...],
    'externalHours' => ['total' => float, 'records' => []], // 0 until Hito 12
    'closureProjection' => ['estimated_date' => str|null, 'on_track' => bool],
    'activityCategories' => [...],
    'mostRecentSession' => [...],
    'sectionAverage' => float,
    'evidencesCount' => int,
    'academicYears' => [...],
    'activeYearId' => int,
]
```

#### Representante

```php
[
    'studentName' => str,
    'progress' => ['accumulated' => float, 'quota' => float, 'percentage' => float, 'traffic_light' => str],
    'lastFourWeeksTrend' => [...],
    'nextSession' => [...],
    'healthReminder' => str|null,
    'academicYears' => [...],
    'activeYearId' => int,
]
```

## 7. Page/Component Design

### 7.1 Directory Structure

```
resources/js/
├── pages/
│   ├── dashboard.tsx                    # Admin dashboard
│   ├── profesor/
│   │   └── dashboard.tsx
│   ├── estudiante/
│   │   └── dashboard.tsx
│   └── representante/
│       └── dashboard.tsx
├── components/dashboard/
│   ├── traffic-light-badge.tsx          # Green/Yellow/Red indicator
│   ├── progress-bar.tsx                 # Animated progress with percentage
│   ├── stat-card.tsx                    # Reusable KPI card
│   ├── alert-banner.tsx                 # Warning/alert display
│   ├── section-ranking-table.tsx        # Admin: section comparison
│   ├── student-progress-list.tsx        # Profesor: per-student bars
│   ├── session-history-table.tsx        # Estudiante: session log
│   ├── category-distribution.tsx        # Hours by category (bar chart)
│   ├── location-heatmap.tsx             # Admin: hours by location
│   └── year-filter.tsx                  # Academic year selector
└── types/
    └── dashboard.ts                     # All TypeScript interfaces
```

### 7.2 Traffic Light System (Visual)

| Status    | Color            | Condition                                                       |
| --------- | ---------------- | --------------------------------------------------------------- |
| 🟢 Green  | `bg-emerald-500` | `progress_pct >= expected_pct`                                  |
| 🟡 Yellow | `bg-amber-500`   | `progress_pct > 0 && progress_pct < expected_pct`               |
| 🔴 Red    | `bg-red-500`     | `progress_pct == 0` OR (`progress_pct < 30%` AND past mid-year) |

### 7.3 Design Principles

- **No business logic in React**: Components receive pre-calculated values (percentages, statuses, labels).
- **Spanish labels**: All UI text in Spanish.
- **TypeScript strict**: Every prop typed with interfaces.
- **Responsive**: Mobile-first with Tailwind CSS 4.
- **Reusable components**: Shared across role dashboards.

## 8. Testing Strategy

### 8.1 HourAccumulatorServiceTest (Unit)

- Test `getStudentTotalHours` with known attendance data.
- Test traffic light boundaries (green/yellow/red thresholds).
- Test `getExpectedHours` linear interpolation.
- Test `getProjectedClosureDate` with various accumulation rates.
- Test with soft-deleted records (should be excluded).

### 8.2 Role Dashboard Tests (Feature)

#### AdminDashboardTest

- Admin sees global compliance, section ranking, alerts.
- Non-admin cannot access admin-specific data.
- Year filter works correctly.
- Default to active year.

#### ProfesorDashboardTest

- Profesor sees only their assigned sections' students.
- Profesor sees their own session stats.
- Cannot see other teachers' data.
- Health condition reminders visible.

#### EstudianteDashboardTest

- Student sees own progress only.
- Section average comparison is visible but discreet.
- Closure projection calculation is correct.

#### RepresentanteDashboardTest

- Representative sees their represented student's data.
- Read-only (no edit actions).
- Student name prominently displayed.
- Health reminder without medical details.

### 8.3 Test Data Strategy

- Use existing factories (`AttendanceFactory`, `AttendanceActivityFactory`, `FieldSessionFactory`).
- Create helper trait `CreatesHourAccumulationData` for test setup.
- Tests use `RefreshDatabase` with `amantina_app_testing` database.

## 9. Implementation Approach

### Phase 1: Foundation (Tasks 1-4)

1. Create `HourAccumulatorService` with core methods (total hours, breakdown, traffic light).
2. Write unit tests for the service.
3. Create `DashboardController` with role routing.
4. Update `routes/web.php` to use the controller.

### Phase 2: Admin Dashboard (Tasks 5-7)

5. Build admin dashboard React page with all sections.
6. Create shared dashboard components (stat cards, traffic light, progress bar).
7. Write admin dashboard feature test.

### Phase 3: Profesor Dashboard (Tasks 8-9)

8. Build profesor dashboard React page.
9. Write profesor dashboard feature test.

### Phase 4: Estudiante Dashboard (Tasks 10-11)

10. Build estudiante dashboard React page.
11. Write estudiante dashboard feature test.

### Phase 5: Representante Dashboard (Tasks 12-13)

12. Build representante dashboard React page.
13. Write representante dashboard feature test.

### Phase 6: Polish (Tasks 14-15)

14. Cross-role testing: verify access control, year filtering, edge cases.
15. Performance review: check query counts, add caching if needed.

## 10. Rollback Plan

1. **Route rollback**: Revert `routes/web.php` to `Route::inertia('dashboard', 'dashboard')`.
2. **Service removal**: Delete `app/Services/HourAccumulatorService.php` — no database changes to revert.
3. **Page removal**: Delete new React pages and components — original `dashboard.tsx` placeholder can be restored from git.
4. **No database migrations**: Since no new tables/columns are added, there is zero database rollback needed.
5. **Git-based**: All changes are in a single branch; `git checkout main` restores previous state.

## 11. Risks and Mitigations

| Risk                          | Impact                   | Mitigation                                                               |
| ----------------------------- | ------------------------ | ------------------------------------------------------------------------ |
| N+1 queries on large datasets | Performance degradation  | Use eager loading, `withSum`, `withCount`; profile with Laravel Debugbar |
| Complex aggregation queries   | Slow dashboard load      | Start with Eloquent, add materialized view only if needed                |
| Traffic light edge cases      | Incorrect status display | Comprehensive unit tests for boundary conditions                         |
| Role confusion in controller  | Wrong data shown         | Feature tests per role verifying data visibility                         |
| Hito 12 integration later     | Refactoring needed       | Aditive design: `total = jornada + external`, external defaults to 0     |
| Large payload size            | Slow Inertia responses   | Use deferred props for non-critical sections, paginate where applicable  |

## 12. TypeScript Interfaces

```typescript
// resources/js/types/dashboard.ts

export interface TrafficLightStatus {
    status: 'green' | 'yellow' | 'red';
    label: string;
    color: string;
}

export interface StudentProgress {
    id: number;
    name: string;
    cedula: string;
    accumulated_hours: number;
    quota: number;
    progress_pct: number;
    traffic_light: TrafficLightStatus;
    section_name?: string;
    recent_health_conditions?: string[];
}

export interface SectionRanking {
    section_id: number;
    section_name: string;
    grade_name: string;
    average_progress: number;
    student_count: number;
    traffic_light: TrafficLightStatus;
}

export interface GlobalCompliance {
    total_students: number;
    met_quota: number;
    on_track: number;
    at_risk: number;
    compliance_pct: number;
}

export interface SessionStats {
    completed: number;
    cancelled: number;
    total_hours: number;
    cancellation_reasons: { reason: string; count: number }[];
}

export interface HoursByCategory {
    category_name: string;
    total_hours: number;
    session_count: number;
}

export interface HoursByLocation {
    location_name: string;
    total_hours: number;
    session_count: number;
}

export interface ClosureProjection {
    estimated_date: string | null;
    on_track: boolean;
    rate_per_week: number;
    weeks_remaining: number | null;
}

export interface AcademicYearOption {
    id: number;
    name: string;
    is_active: boolean;
}

// Admin dashboard props
export interface AdminDashboardProps {
    globalCompliance: GlobalCompliance;
    sectionRanking: SectionRanking[];
    hoursByTerm: { term_name: string; total_hours: number }[];
    sessionStats: SessionStats;
    transferredWithPendingHours: StudentProgress[];
    alerts: {
        zero_hour_students: StudentProgress[];
        sessions_without_attention: {
            id: number;
            name: string;
            date: string;
        }[];
    };
    yearOverYearComparison: {
        year: string;
        total_hours: number;
        student_count: number;
    }[];
    topActivityCategories: HoursByCategory[];
    averageHoursPerSession: number;
    teacherSessionCounts: {
        teacher_name: string;
        session_count: number;
        total_hours: number;
    }[];
    averageAttendanceRate: number;
    hoursByLocation: HoursByLocation[];
    academicYears: AcademicYearOption[];
    activeYearId: number;
}

// Profesor dashboard props
export interface ProfesorDashboardProps {
    students: StudentProgress[];
    ownSessionStats: SessionStats;
    pendingAttendance: {
        id: number;
        name: string;
        date: string;
        section: string;
    }[];
    lowAttendanceStudents: StudentProgress[];
    hoursByCategory: HoursByCategory[];
    averageHoursPerStudent: number;
    sessionsPerTerm: { term_name: string; session_count: number }[];
    recentHealthReminders: { student_name: string; condition: string }[];
    academicYears: AcademicYearOption[];
    activeYearId: number;
}

// Estudiante dashboard props
export interface EstudianteDashboardProps {
    progress: {
        accumulated: number;
        quota: number;
        percentage: number;
        traffic_light: TrafficLightStatus;
    };
    breakdownByYear: { year: string; hours: number; quota: number }[];
    breakdownByTerm: { term: string; hours: number }[];
    sessionHistory: {
        date: string;
        activity: string;
        location: string;
        hours: number;
        category: string;
    }[];
    externalHours: { total: number; records: unknown[] };
    closureProjection: ClosureProjection;
    activityCategories: HoursByCategory[];
    mostRecentSession: {
        date: string;
        location: string;
        hours: number;
        activity: string;
    } | null;
    sectionAverage: number;
    evidencesCount: number;
    academicYears: AcademicYearOption[];
    activeYearId: number;
}

// Representante dashboard props
export interface RepresentanteDashboardProps {
    studentName: string;
    progress: {
        accumulated: number;
        quota: number;
        percentage: number;
        traffic_light: TrafficLightStatus;
    };
    lastFourWeeksTrend: { week: string; hours: number }[];
    nextSession: { date: string; activity: string; location: string } | null;
    healthReminder: string | null;
    academicYears: AcademicYearOption[];
    activeYearId: number;
}
```
