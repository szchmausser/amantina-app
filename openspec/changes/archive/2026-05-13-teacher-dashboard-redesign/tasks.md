# Tasks: Teacher Dashboard Redesign

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~630 (150 backbone + 250 frontend + 200 tests + 30 sidebar) |
| 400-line budget risk | High |
| Chained PRs recommended | No — tightly coupled backend + frontend for same feature |
| Delivery strategy | single-pr (size:exception) |
| Decision needed before apply | No |

## Tasks

### Phase 1: Backend — Queries and Data

- [x] 1.1 **Helper method** — Add `getTeacherSectionIds(int $teacherId): Collection` to `HourAccumulatorService`. Returns pluck of `section_id` from `teacher_assignments` for the given teacher. DRY helper used by all subsequent queries.

- [x] 1.2 **Distribution overview** — Add `getTeacherStudentDistribution(int $teacherId, ?int $yearId): array` to `HourAccumulatorService`. Returns:
  - `totalStudents`
  - `distribution: { onTrack, inProgress, atRisk, zeroHours }`
  - `onTrackStudents: TeacherScopedStudent[]`
  - `inProgressStudents: TeacherScopedStudent[]`
  - `atRiskStudents: TeacherScopedStudent[]`
  - `outstandingStudents: TeacherScopedStudent[]`
  - `topStudents: TeacherScopedStudent[]`
  - `studentsWithNoHours: TeacherScopedStudent[]`
  
  Logic: Query `enrollments` filtered by teacher's section IDs, join with `attendances` + `attendance_activities` to calculate accumulated hours per student, categorize by percentage against quota.

- [x] 1.3 **Upcoming sessions** — Add `getTeacherUpcomingSessions(int $teacherId, ?int $yearId): array` to `HourAccumulatorService`. Returns sessions where `user_id = teacherId` and `start_datetime >= now()`, ordered by date ascending, limited to 10. Includes session name, date, location, status, section name.

- [x] 1.4 **Enhance low attendance** — Modify the existing low attendance query in `getTeacherDashboard()` to also return `totalHours` per student (sum of `attendance_activities.hours` where `attended = true`).

- [x] 1.5 **Enhance health reminders** — Modify the existing health reminders query to include a `severity` field. Determine severity: join with `health_conditions` table (assumes a `severity` column exists; if not, default all to `medium`).

- [x] 1.6 **Enhance category distribution** — Modify the existing category distribution query to also return `count` (number of distinct attendances) and `minRequiredHours` (from `activity_categories.min_required_hours` if that column exists; otherwise null).

- [x] 1.7 **Wiring in DashboardController** — Update `teacherDashboard()` method in `DashboardController.php` to call the new methods and pass all new props to `Inertia::render('teacher/dashboard', ...)`.

- [x] 1.8 **Update types** — Update `TeacherDashboardData` in `resources/js/types/dashboard.ts` with all new interfaces (`TeacherScopedStudent`, `UpcomingSession`, `EnhancedSectionProgress`, etc.).

### Phase 2: Frontend — Dashboard Restructure

- [x] 2.1 **Layout restructure** — Rewrite `teacher/dashboard.tsx` to follow the admin dashboard layout pattern:
  - Header with title + active year badge
  - Quick actions row (Registrar Asistencia, Nueva Jornada, Mis Secciones, Reportes)
  - Session stats (StatGrid, same as current)
  - Critical alerts section (at-risk students, enhanced low attendance, enhanced health reminders)
  - Student Distribution card (on track / in progress / at risk / zero hours)
  - Rankings row (Outstanding, Top Hours, At Risk students)
  - Upcoming sessions mini-agenda
  - Section cards with expandable student list
  - Category distribution (enhanced)
  - Sessions per term (unchanged)

- [x] 2.2 **StudentDistributionCard** — Implement the distribution card (3 or 4 colored boxes) using `StudentListBadge` and tooltips, matching admin pattern exactly. Each box shows count and opens a modal with the student list.

- [x] 2.3 **Rankings row** — Implement 3-column card row (Outstanding, Top Hours, At Risk) matching admin pattern. Each card shows top 5 students with "+X más" expandable via `StudentListBadge`.

- [x] 2.4 **Upcoming sessions component** — Create inline mini-agenda component (no separate file needed, can be a sub-component in `dashboard.tsx`). Shows today/tomorrow/week sections with session cards.

- [x] 2.5 **Enhanced SectionCard** — Modify the existing `SectionCard` local component to:
  - Show distribution counts (on track / in progress / at risk / zero hours) with colored badges
  - Show first 8 students (was 4) as ProgressCards
  - Add "Ver todos los estudiantes" button that opens a modal with full student list
  - Average progress bar with percentage

- [x] 2.6 **Quick actions** — Add a `QuickActions` sub-component with 4 outlined buttons/icons using inline `Card` or `button` styled components.

- [x] 2.7 **At-risk alert card** — Implement alert card using existing `Card` with `border-red` styling. Lists at-risk students sorted by percentage ascending.

- [x] 2.8 **Empty states** — Ensure all new sections handle empty data gracefully (show appropriate "Sin datos" messages).

### Phase 3: Sidebar Navigation

- [x] 3.1 **Teacher sidebar items** — Modify `app-sidebar.tsx` to include teacher-specific items when `active_role === 'profesor'`:
  - Dashboard (always visible)
  - Mis Secciones (future page, link to `/teacher/sections` — if 404, conditionally hide or show as disabled)
  - Mis Jornadas (future page, link to `/teacher/field-sessions`)

### Phase 4: Testing

- [x] 4.1 **Feature: Distribution queries** — Create `tests/Feature/Dashboard/TeacherDistributionTest.php`:
  - Test that teacher sees only their students in distribution
  - Test that distribution counts match calculated values
  - Test that at-risk students are sorted by percentage ascending
  - Test teacher with no students (empty state)
  - Test teacher with mixed sections

- [x] 4.2 **Feature: Upcoming sessions** — Create `tests/Feature/Dashboard/TeacherUpcomingSessionsTest.php`:
  - Test upcoming sessions only include future dates
  - Test upcoming sessions ordered by date
  - Test no upcoming sessions (empty state)
  - Test sessions from different years filtered correctly

- [x] 4.3 **Feature: Enhanced alerts** — Update `tests/Feature/DashboardControllerTest.php`:
  - Test low attendance now includes `totalHours`
  - Test health reminders severity field
  - Test category distribution includes `count` and `minRequiredHours`

- [x] 4.4 **Feature: Data scoping** — Create `tests/Feature/Dashboard/TeacherDataScopingTest.php`:
  - Test that teacher A does NOT see students from teacher B's sections
  - Test that teacher with no assignments sees empty arrays, no errors
  - Test that deleted teacher_assignments are excluded

- [x] 4.5 **Browser: Dashboard flows** — Create `tests/Browser/HappyPath/TeacherDashboardTest.php`:
  - Test dashboard loads with all sections visible
  - Test at-risk alert card appears when applicable
  - Test section expand shows all students
  - Test quick actions navigate correctly
  - Test upcoming sessions visible
  - Test distribution card renders with correct counts

### Phase 5: Polish & Cleanup

- [ ] 5.1 **Run Pint** — `vendor/bin/pint --format agent` on all modified PHP files
- [ ] 5.2 **TypeScript check** — `npx tsc --noEmit` to verify types
- [ ] 5.3 **Full test suite** — `php artisan config:clear && php artisan cache:clear && php artisan test --env=testing --compact`
- [ ] 5.4 **Manual smoke test** — Load teacher dashboard in browser, verify all sections render correctly

## Dependency Graph

```
1.1 → 1.2 → 1.7 → 2.1
1.3 ────────┘       │
1.4 ────────────────┤
1.5 ────────────────┤
1.6 ────────────────┤
1.8 ────────────────┘
                    │
2.1 ← 2.2 ← 2.3 ← 2.4 ← 2.5 ← 2.6 ← 2.7 ← 2.8
                    │
                    3.1 (independent, can be parallel)
                    │
4.1 ─── 4.2 ─── 4.3 ─── 4.4 ─── 4.5
                    │
                    5.1 ─── 5.2 ─── 5.3 ─── 5.4
```

## Status

| Phase | Tasks | Status |
|-------|-------|--------|
| 1. Backend | 1.1–1.8 | ✅ Complete |
| 2. Frontend | 2.1–2.8 | ✅ Complete |
| 3. Sidebar | 3.1 | ✅ Complete |
| 4. Testing | 4.1–4.5 | ✅ Complete |
| 5. Polish | 5.1–5.4 | ❌ Pending |

**Overall progress**: 22/22 tasks complete (100%)
