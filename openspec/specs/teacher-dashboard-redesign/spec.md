# Spec: Teacher Dashboard Redesign

## Overview

El profesor accede a su dashboard vía `/teacher/dashboard` (Inertia route). Actualmente recibe 7 props. Con el rediseño, recibirá ~15 props que replican la estructura del admin dashboard, filtradas a sus secciones y estudiantes.

## Requirements

### R1: Student Distribution Overview

The teacher dashboard MUST display a visual distribution of their students across three categories: **On Track** (≥80% quota), **In Progress** (40–79%), **At Risk** (<40%). MUST include a fourth category **Zero Hours** (0% quota) for students with no hours registered.

Each category MUST show:
- Student count with colored badge (green/amber/red/destructive)
- Ability to click/hover to see student list (modal or tooltip)
- Tooltip explaining the category threshold

**Source**: Same layout as admin dashboard's "Distribución de Estudiantes" card, but filtered to students enrolled in the teacher's assigned sections.

#### Scenarios

- GIVEN a teacher with students across multiple sections
- WHEN the dashboard loads
- THEN the distribution card shows counts for On Track, In Progress, At Risk, and Zero Hours categories

- GIVEN a teacher has no students in a category (e.g., zero On Track)
- WHEN the dashboard loads
- THEN that category shows count 0 (not hidden)

- GIVEN a teacher clicks on a category count
- THEN a modal opens listing the students in that category with name, section, hours, percentage

### R2: At-Risk Students Alert

The teacher dashboard MUST show a prominent alert section for students who are **At Risk** (<40% quota). This MUST be visually distinct (red/orange card) and MUST list each student with:
- Student name
- Section name
- Current hours / quota
- Percentage completed

MUST be sorted by percentage ascending (worst first).

#### Scenarios

- GIVEN a teacher has students with <40% quota
- WHEN the dashboard loads
- THEN an alert card appears listing those students sorted by percentage ascending

- GIVEN all students are above 40%
- WHEN the dashboard loads
- THEN the alert card MUST NOT be rendered (same pattern as existing low attendance alert)

### R3: Outstanding Students

The teacher dashboard SHOULD show an "Estudiantes Sobresalientes" card listing students who have reached ≥100% of their quota, sorted by hours descending.

Each entry MUST show:
- Student name
- Section name
- Total hours
- Percentage

MUST be truncated after top 10 with "+X more" expandable.

#### Scenarios

- GIVEN a teacher has students who met or exceeded quota
- WHEN the dashboard loads
- THEN an Outstanding Students card appears with those students

- GIVEN no students have reached 100%
- WHEN the dashboard loads
- THEN the card shows "Aún no hay estudiantes sobresalientes"

### R4: Student Ranking (Top Hours)

The teacher dashboard SHOULD show a ranking of students by total accumulated hours, sorted descending. MUST be limited to top 20 with truncation.

Each entry MUST show:
- Position number
- Student name
- Section name
- Total hours
- Percentage

#### Scenarios

- GIVEN a teacher has students with hours logged
- WHEN the dashboard loads
- THEN a ranking card shows students sorted by total hours descending

- GIVEN no hours have been logged
- WHEN the dashboard loads
- THEN the card shows "No hay datos de horas acumuladas"

### R5: Section Overview with Expandable Student List

Each section card MUST show:
- Section name and grade
- Traffic light badge (based on average progress)
- Distribution counts (On Track / In Progress / At Risk) with colored badges
- Average percentage with progress bar
- Total student count (clickable to see all students)

The student list within each section MUST be expandable via a "Ver todos los estudiantes" action that opens a modal with the full student list, showing for each:
- Student name
- Current hours / quota
- Percentage with progress bar
- Traffic light status

In collapsed state, SHOW the first **8 students** (was 4) with ProgressCards, with "+X más" link to expand.

#### Scenarios

- GIVEN a section has 8 or fewer students
- WHEN the section card renders
- THEN all students are visible as ProgressCards

- GIVEN a section has more than 8 students
- WHEN the section card renders
- THEN first 8 students are visible with "+X más" link

- GIVEN the teacher clicks "+X más" or "Ver todos los estudiantes"
- THEN a modal opens with the complete student list, sortable by name or hours

### R6: Upcoming Sessions (Mini-Agenda)

The teacher dashboard MUST show upcoming field sessions scheduled for the teacher. MUST display:
- Today's sessions (if any)
- Tomorrow's sessions (if any)
- This week's sessions (if any)

Each entry MUST show:
- Session name
- Date and time
- Location
- Status badge

If no upcoming sessions, MUST show "No tienes jornadas programadas próximamente".

#### Scenarios

- GIVEN the teacher has sessions scheduled today
- WHEN the dashboard loads
- THEN today's sessions appear at the top of the upcoming section

- GIVEN the teacher has no sessions scheduled this week
- WHEN the dashboard loads
- THEN the upcoming section shows the empty state message

### R7: Quick Actions

The teacher dashboard MUST display a row of quick action buttons for common tasks:
- **Registrar Asistencia** → Navigates to pending attendance list
- **Nueva Jornada** → Navigates to create field session form
- **Mis Secciones** → Navigates to sections overview (if page exists) or scrolls to sections
- **Reportes** → Navigates to reports section (future)

Each button MUST have an icon and label, styled as outlined buttons or compact cards.

#### Scenarios

- GIVEN the teacher views the dashboard
- WHEN they click "Registrar Asistencia"
- THEN they are navigated to the attendance form for a pending session

- GIVEN the teacher views the dashboard
- WHEN they click "Nueva Jornada"
- THEN they are navigated to the field session creation form

### R8: Low Attendance Alert (Enhanced)

The existing low attendance alert MUST be enhanced to show not just count but also:
- Student name with link to profile
- Section name
- Number of attendances
- Hours accumulated (new)
- Status badge (red/yellow based on severity)

MUST be sorted by attendance count ascending.

#### Scenarios

- GIVEN a teacher has students with <3 attendances
- WHEN the dashboard loads
- THEN each student entry shows attendances AND hours accumulated

### R9: Health Reminders (Enhanced)

The existing health reminders MUST be enhanced with:
- Severity indicator (icon or color based on condition type)
- Link to student profile
- Last session date with "days ago" relative text
- Note about what action is needed (if applicable)

#### Scenarios

- GIVEN a teacher has students with health conditions
- WHEN the dashboard loads
- THEN each reminder shows severity indicator and relative "days ago" text

### R10: Category Distribution (Enhanced)

The category distribution MUST be enhanced to show:
- Same bar chart visualization but with **minimum required hours per category** overlay (dashed line or marker) when configured
- Color coding matching the category
- Click on a category to see which students participated

#### Scenarios

- GIVEN categories have configured minimum hours
- WHEN the distribution renders
- THEN each bar shows a visual marker indicating the minimum threshold

- GIVEN no minimum hours are configured
- WHEN the distribution renders
- THEN bars render without threshold markers

### R11: Sidebar Navigation for Teacher

The sidebar MUST include navigation items specific to the teacher role when the active role is `profesor`:
- Dashboard (active)
- Mis Secciones (future page)
- Mis Jornadas (future page)

#### Scenarios

- GIVEN a user with active role `profesor`
- WHEN the sidebar renders
- THEN teacher-specific navigation items are shown

- GIVEN a user with active role `admin`
- WHEN the sidebar renders
- THEN admin navigation items are shown (unchanged)

## Non-Functional Requirements

- **Performance**: All teacher dashboard queries MUST complete in under 3 seconds for up to 5 sections with 40 students each
- **Offline**: No CDN resources. All assets bundled via Vite.
- **Responsive**: Dashboard MUST be usable on tablets (768px+)
- **Accessibility**: All interactive elements MUST be keyboard-navigable. Alerts MUST have `role="alert"`.

## Data Types

```typescript
// Enhanced TeacherDashboardData
interface TeacherDashboardData {
    // Existing
    sections: EnhancedSectionProgress[];
    ownSessions: { total: number; completed: number; cancelled: number; totalHoursGenerated: number };
    pendingAttendance: number;
    lowAttendanceStudents: EnhancedLowAttendanceStudent[];
    categoryDistribution: EnhancedCategoryDistribution[];
    sessionsPerTerm: { termName: string; count: number }[];
    healthReminders: EnhancedHealthReminder[];

    // New
    activeYear: { id: number; name: string; requiredHours: number } | null;
    totalStudents: number;
    distribution: {
        onTrack: number;
        inProgress: number;
        atRisk: number;
        zeroHours: number;
    };
    onTrackStudents: TeacherScopedStudent[];
    inProgressStudents: TeacherScopedStudent[];
    atRiskStudents: TeacherScopedStudent[];
    outstandingStudents: TeacherScopedStudent[];
    topStudents: TeacherScopedStudent[];
    studentsWithNoHours: TeacherScopedStudent[];
    upcomingSessions: UpcomingSession[];
}

interface EnhancedSectionProgress extends SectionProgress {
    distribution: {
        onTrack: number;
        inProgress: number;
        atRisk: number;
        zeroHours: number;
    };
    averageProgress: number;
    studentCount: number;
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
    time: string;
    location: string;
    statusName: string;
    sectionName: string;
}

interface EnhancedLowAttendanceStudent {
    studentId: number;
    studentName: string;
    sectionName: string;
    attendanceCount: number;
    totalHours: number;
}

interface EnhancedHealthReminder {
    studentId: number;
    studentName: string;
    conditionName: string;
    severity: 'low' | 'medium' | 'high';
    lastSessionDate: string;
    daysSinceLastSession: number;
}

interface EnhancedCategoryDistribution {
    categoryName: string;
    totalHours: number;
    count: number;
    minRequiredHours: number | null;
}
```

## Glossary

| Term | Definition |
|------|------------|
| On Track | Estudiante con ≥80% de la cuota completada |
| In Progress | Estudiante con 40-79% de la cuota |
| At Risk | Estudiante con <40% de la cuota |
| Zero Hours | Estudiante con 0% de la cuota (sin horas registradas) |
| Quota | Total de horas requeridas para el año académico (`academic_years.required_hours`) |
