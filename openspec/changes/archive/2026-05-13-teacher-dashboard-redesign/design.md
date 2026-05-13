# Design: Teacher Dashboard Redesign

## Technical Approach

Portar la arquitectura de datos y componentes del `admin/dashboard.tsx` al `teacher/dashboard.tsx`, filtrando todas las queries por `teacher_assignments`. El backend agrega nuevos métodos en `HourAccumulatorService` que replican `getInstitutionOverview()` pero scoped al teacher. El frontend reestructura la página usando los mismos componentes UI ya existentes y probados.

## Architecture Decisions

### Decision: Reuse admin components vs create teacher-specific components

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Reuse `StudentListBadge`, modales, tooltips, SectionCard | Consistencia visual, cero código UI nuevo, ya probados | **Accept** — Los componentes son genéricos, solo cambian los datos. |
| Crear componentes específicos para teacher | Más control pero duplicación, más tests | **Skip** — Infla el códigobase innecesariamente. |

### Decision: Backend queries — new methods vs parameterized existing methods

| Option | Tradeoff | Decision |
|--------|----------|----------|
| New method `getTeacherInstitutionOverview()` en HourAccumulatorService | Clara separación de responsabilidades, no toca lo existente | **Accept** — Replicar lógica de `getInstitutionOverview` pero filtrando por `section_id IN (teacher_sections)`. |
| Pasar `teacherId` opcional a `getInstitutionOverview` | Menos código pero acopla lógica de admin y teacher | **Skip** — Las queries tienen suficientes diferencias (JOINs) como para justificar método separado. |

### Decision: Student list modal — generic vs section-specific

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Same `StudentListBadge` modal pattern as admin | Ya existe, probado, consistente | **Accept** — Reusar `StudentListBadge` con `showModal` prop. |
| New inline expandable table | Más interactivo pero más código y tests | **Skip** — Modal es suficiente para primera iteración. Expandible inline puede ir en iteración futura. |

### Decision: Upcoming sessions — backend query vs frontend filter

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Backend query: `field_sessions` WHERE `user_id=teacher` AND `start_datetime >= NOW()` | Precisa, una query, ordenada | **Accept** — Backend ya tiene la data, solo falta filtrar por fecha futura. |
| Frontend filter sobre `ownSessions` | Datos desactualizados, mezcla pasadas y futuras | **Skip** — No hay garantía de que `ownSessions` incluya sesiones futuras. |

### Decision: Sidebar items for teacher — conditional rendering vs separate config

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Single sidebar config with role-based `visible` | Un solo archivo, fácil de mantener | **Accept** — Ya existe lógica de permisos en `app-sidebar.tsx`. Extender para profesor. |
| Separate sidebar config per role | Más archivos, sincronización | **Skip** — Infla sin necesidad. |

## Data Flow

```
[Browser] GET /teacher/dashboard
  → DashboardController::__invoke()
    → session('active_role') === 'profesor'
    → teacherDashboard($user, $activeYear)
      → HourAccumulatorService::getTeacherDashboard($teacherId, $yearId)
        → getQuota()
        → getTeacherSections()  // new: teacher_assignments → sections → grades
        → For each section:
            → getSectionProgress($sectionId, $yearId)
            → calculate student statuses
        → getTeacherOwnSessions()  // existing, enhanced
        → getTeacherPendingAttendance()  // existing
        → getTeacherAtRiskStudents()  // NEW
        → getTeacherOutstandingStudents()  // NEW
        → getTeacherTopStudents()  // NEW
        → getTeacherZeroHourStudents()  // NEW
        → getTeacherDistribution()  // NEW: aggregate counts per category
        → getTeacherUpcomingSessions()  // NEW
        → getTeacherLowAttendanceStudents()  // existing, enhanced
        → getTeacherHealthReminders()  // existing, enhanced
        → getTeacherCategoryDistribution()  // existing, enhanced
        
      → Inertia::render('teacher/dashboard', { ...props })
        
  → React renders TeacherDashboard
    → StatGrid with session stats + quick actions
    → Alerts (at risk, low attendance, health)
    → Student Distribution card (on track / progress / risk / zero)
    → Rankings row (outstanding, top hours, at risk)
    → Upcoming sessions mini-agenda
    → Sections cards with expandable student list
    → Category distribution (enhanced)
    → Sessions per term (unchanged)
```

## Data Structures (Backend)

```php
// HourAccumulatorService — new method
public function getTeacherInstitutionOverview(int $teacherId, ?int $yearId): array
{
    $teacherSectionIds = DB::table('teacher_assignments')
        ->where('user_id', $teacherId)
        ->whereNull('deleted_at')
        ->pluck('section_id');

    // Same logic as getInstitutionOverview() but adding:
    // ->whereIn('enrollments.section_id', $teacherSectionIds)
    // to every enrollment-based query.
    
    return [
        'totalStudents' => ...,
        'distribution' => [...],
        'onTrackStudents' => [...],
        'inProgressStudents' => [...],
        'atRiskStudents' => [...],
        'outstandingStudents' => [...],
        'topStudents' => [...],
        'studentsWithNoHours' => [...],
        'alerts' => [...],
    ];
}
```

```php
// HourAccumulatorService — new method
public function getTeacherUpcomingSessions(int $teacherId, ?int $yearId): array
{
    return DB::table('field_sessions')
        ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
        ->join('sections', 'field_sessions.section_id', '=', 'sections.id')
        ->where('field_sessions.user_id', $teacherId)
        ->where('field_sessions.start_datetime', '>=', now())
        ->whereNull('field_sessions.deleted_at')
        ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
        ->orderBy('field_sessions.start_datetime')
        ->limit(10)
        ->get()
        ->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'date' => $r->start_datetime,
            'location' => $r->location,
            'statusName' => $r->name, // from statuses table
            'sectionName' => $r->name, // from sections table
        ]);
}
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Services/HourAccumulatorService.php` | Modify | Add `getTeacherInstitutionOverview()`, `getTeacherUpcomingSessions()`, enhance existing methods |
| `app/Http/Controllers/DashboardController.php` | Modify | Map new props from `getTeacherDashboard()` to Inertia render |
| `resources/js/pages/teacher/dashboard.tsx` | Rewrite | Full restructure using admin components pattern |
| `resources/js/types/dashboard.ts` | Modify | Update `TeacherDashboardData` interface |
| `resources/js/components/app-sidebar.tsx` | Modify | Add teacher-specific navigation items |
| `tests/Feature/DashboardControllerTest.php` | Modify | Add tests for new teacher dashboard props |
| `tests/Browser/HappyPath/TeacherDashboardTest.php` | New | Browser tests for teacher dashboard flows |

## Interfaces / Contracts

```typescript
// Updated TeacherDashboardData
interface TeacherDashboardData {
    // Base info
    activeYear: { id: number; name: string; requiredHours: number } | null;
    
    // Session stats (existing, keep)
    ownSessions: { total: number; completed: number; cancelled: number; totalHoursGenerated: number };
    pendingAttendance: number;
    
    // Student distribution (NEW — matches admin pattern)
    totalStudents: number;
    distribution: { onTrack: number; inProgress: number; atRisk: number; zeroHours: number };
    onTrackStudents: TeacherScopedStudent[];
    inProgressStudents: TeacherScopedStudent[];
    atRiskStudents: TeacherScopedStudent[];
    outstandingStudents: TeacherScopedStudent[];
    topStudents: TeacherScopedStudent[];
    studentsWithNoHours: TeacherScopedStudent[];
    
    // Sections (enhanced)
    sections: EnhancedSectionProgress[];
    
    // Alerts (enhanced)
    lowAttendanceStudents: EnhancedLowAttendanceStudent[];
    healthReminders: EnhancedHealthReminder[];
    
    // Agenda (NEW)
    upcomingSessions: UpcomingSession[];
    
    // Stats (existing, keep)
    categoryDistribution: EnhancedCategoryDistribution[];
    sessionsPerTerm: { termName: string; count: number }[];
}

interface TeacherScopedStudent {
    id: number;
    name: string;
    sectionName: string;
    gradeName: string;
    hours: number;
    quota: number;
    percentage: number;
    status: TrafficLightStatus;
}

interface UpcomingSession {
    id: number;
    name: string;
    date: string;
    location: string;
    statusName: string;
    sectionName: string;
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | Teacher dashboard returns at-risk students | Assert `atRiskStudents` array contains expected students |
| Feature | Teacher dashboard returns distribution counts | Assert `distribution` matches calculated values |
| Feature | Teacher dashboard only includes teacher's students | Assert no student from other teacher's sections appears |
| Feature | Teacher dashboard returns upcoming sessions | Assert `upcomingSessions` contains future sessions only |
| Feature | Teacher with no sections sees empty state | Assert empty arrays, no errors |
| Feature | Teacher dashboard doesn't include global data | Assert no student from unrelated sections |
| Browser | Teacher sees at-risk alert card | Assert alert card is visible when at-risk students exist |
| Browser | Teacher can expand section to see all students | Click expand, assert all students visible |
| Browser | Teacher sees quick actions buttons | Assert buttons rendered and clickable |
| Browser | Teacher sees upcoming sessions | Assert upcoming sessions card has content |

## PR Boundary

| Unit | Lines | Description |
|------|-------|-------------|
| Backend (queries) | ~150 | New methods in HourAccumulatorService + DashboardController wiring |
| Frontend (restructure) | ~250 | Rewrite teacher/dashboard.tsx, update types |
| Sidebar | ~30 | Teacher navigation items |
| Tests | ~200 | Feature + Browser tests |
| **Total** | **~630** | |

**Recommendation**: Single PR with `size:exception` tag. The changes are tightly coupled (backend + frontend + tests for same feature). Chaining would require two PRs where PR1 is backend-only and PR2 is frontend-only, but the frontend changes depend on seeing the new props — they ship together naturally.

## Migration / Rollout

- No new migrations required (all data already exists)
- No new packages required
- Cache clear recommended after deployment
- Feature flags: not needed (teacher dashboard is internal-only)

## Open Questions

- [ ] ¿Debemos crear una página independiente "Mis Secciones" para profesor, o por ahora usamos solo los modales expandibles del dashboard? → Decisión tentativa: Solo modales en dashboard. Páginas independientes serían cambio futuro.
- [ ] ¿Debemos agregar ordenamiento (por nombre, por horas) en el modal de estudiantes de una sección? → Decisión tentativa: Primera iteración solo lista simple ordenada por horas descendente. Ordenamiento en futura iteración.
