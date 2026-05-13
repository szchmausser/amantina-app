<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\HourAccumulatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected HourAccumulatorService $hourAccumulator
    ) {}

    /**
     * Route to the appropriate dashboard based on user role.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $yearId = $request->integer('year');

        // Resolve year: use requested year, fallback to active year
        $activeYear = $yearId
            ? AcademicYear::find($yearId)
            : AcademicYear::active()->first();

        // Route to dashboard based on active session role
        $activeRole = $request->session()->get('active_role');

        if ($activeRole === 'admin') {
            return $this->adminDashboard($user, $activeYear, $request);
        }

        if ($activeRole === 'profesor') {
            return $this->teacherDashboard($user, $activeYear, $request);
        }

        if ($activeRole === 'alumno') {
            return $this->studentDashboard($user, $activeYear);
        }

        if ($activeRole === 'representante') {
            return $this->representativeDashboard($user, $activeYear);
        }

        // Fallback: no role assigned
        return Inertia::render('dashboard', [
            'message' => 'No tienes un rol asignado. Contacta al administrador.',
        ]);
    }

    /**
     * Admin dashboard with institution-wide KPIs.
     */
    protected function adminDashboard(User $user, ?AcademicYear $year, Request $request): Response
    {
        $yearId = $year?->id;
        $overview = $this->hourAccumulator->getInstitutionOverview($yearId);

        // Grade/section filters for category distribution
        $gradeId = $request->query('grade_id') ? (int) $request->query('grade_id') : null;
        $sectionId = $request->query('section_id') ? (int) $request->query('section_id') : null;

        $categoryDistribution = $this->hourAccumulator->getAdminCategoryDistribution($yearId, $gradeId, $sectionId);

        // All grades for filter dropdown
        $grades = Grade::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        // All sections for filter dropdown
        $sections = Section::query()
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->whereNull('sections.deleted_at')
            ->whereNull('grades.deleted_at')
            ->orderBy('grades.name')
            ->orderBy('sections.name')
            ->get(['sections.id', 'sections.name', 'sections.grade_id'])
            ->toArray();

        return Inertia::render('admin/dashboard', [
            'activeYear' => $year ? [
                'id' => $year->id,
                'name' => $year->name,
                'requiredHours' => (float) $year->required_hours,
            ] : null,
            'totalStudents' => $overview['totalStudents'] ?? 0,
            'requiredHours' => $overview['requiredHours'] ?? 0,
            'averageHours' => $overview['averageHours'] ?? 0,
            'distribution' => $overview['distribution'] ?? [],
            'onTrackStudents' => $overview['onTrackStudents'] ?? [],
            'inProgressStudents' => $overview['inProgressStudents'] ?? [],
            'atRiskStudents' => $overview['atRiskStudents'] ?? [],
            'outstandingStudents' => $overview['outstandingStudents'] ?? [],
            'topStudents' => $overview['topStudents'] ?? [],
            'studentsWithNoHours' => $overview['studentsWithNoHours'] ?? [],
            'topSections' => $overview['topSections'] ?? [],
            'concerningSections' => $overview['concerningSections'] ?? [],
            'alerts' => $overview['alerts'] ?? [],
            'categoryDistribution' => $categoryDistribution,
            'grades' => $grades,
            'sections' => $sections,
            'selectedGradeId' => $gradeId,
            'selectedSectionId' => $sectionId,
        ]);
    }

    /**
     * Teacher dashboard with section-specific data.
     */
    protected function teacherDashboard(User $user, ?AcademicYear $year, Request $request): Response
    {
        $yearId = $year?->id;
        $teacherId = $user->id;

        // Get sections where the teacher actually has data (from field sessions),
        // not just formal teacher_assignments
        $teacherSectionIds = DB::table('attendances')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->join('enrollments', function ($join) use ($yearId) {
                $join->on('attendances.user_id', '=', 'enrollments.user_id')
                    ->whereNull('enrollments.deleted_at');
                if ($yearId) {
                    $join->where('enrollments.academic_year_id', $yearId);
                }
            })
            ->where('field_sessions.user_id', $teacherId)
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->distinct()
            ->pluck('enrollments.section_id')
            ->merge(
                // Also include sections from teacher_assignments as fallback
                TeacherAssignment::where('user_id', $teacherId)
                    ->whereNull('deleted_at')
                    ->pluck('section_id')
            )
            ->unique()
            ->values();

        $teacherSections = Section::whereIn('id', $teacherSectionIds)
            ->with('grade')->get();

        $grades = $teacherSections->map(fn ($s) => $s->grade)
            ->unique('id')
            ->values()
            ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]);

        // Grade/section filters from query params
        $gradeId = $request->query('grade_id') ? (int) $request->query('grade_id') : null;
        $sectionId = $request->query('section_id') ? (int) $request->query('section_id') : null;

        $data = $this->hourAccumulator->getTeacherDashboard($teacherId, $yearId, $gradeId, $sectionId);
        $distribution = $this->hourAccumulator->getTeacherStudentDistribution($teacherId, $yearId);
        $upcomingSessions = $this->hourAccumulator->getTeacherUpcomingSessions($teacherId, $yearId);

        return Inertia::render('teacher/dashboard', [
            'activeYear' => $year ? [
                'id' => $year->id,
                'name' => $year->name,
                'requiredHours' => (float) $year->required_hours,
            ] : null,
            'sections' => $data['sections'] ?? [],
            'ownSessions' => $data['ownSessions'] ?? [],
            'pendingAttendance' => $data['pendingAttendance'] ?? 0,
            'lowAttendanceStudents' => $data['lowAttendanceStudents'] ?? [],
            'categoryDistribution' => $data['categoryDistribution'] ?? [],
            'sessionsPerTerm' => $data['sessionsPerTerm'] ?? [],
            'healthReminders' => $data['healthReminders'] ?? [],
            // New props from student distribution
            'totalStudents' => $distribution['totalStudents'] ?? 0,
            'distribution' => $distribution['distribution'] ?? [],
            'onTrackStudents' => $distribution['onTrackStudents'] ?? [],
            'inProgressStudents' => $distribution['inProgressStudents'] ?? [],
            'atRiskStudents' => $distribution['atRiskStudents'] ?? [],
            'outstandingStudents' => $distribution['outstandingStudents'] ?? [],
            'topStudents' => $distribution['topStudents'] ?? [],
            'studentsWithNoHours' => $distribution['studentsWithNoHours'] ?? [],
            'upcomingSessions' => $upcomingSessions,
            // Grade/section filter data
            'grades' => $grades->toArray(),
            'filterSections' => $teacherSections->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'grade_id' => $s->grade_id,
            ])->values()->toArray(),
            'selectedGradeId' => $gradeId,
            'selectedSectionId' => $sectionId,
        ]);
    }

    /**
     * Student dashboard with personal progress.
     */
    protected function studentDashboard(User $user, ?AcademicYear $year): Response
    {
        $yearId = $year?->id;
        $data = $this->hourAccumulator->getStudentDashboard($user->id, $yearId);

        return Inertia::render('student/dashboard', [
            'activeYear' => $year ? [
                'id' => $year->id,
                'name' => $year->name,
                'requiredHours' => (float) $year->required_hours,
            ] : null,
            'progress' => $data['progress'] ?? [],
            'breakdownByYear' => $data['breakdownByYear'] ?? [],
            'breakdownByTerm' => $data['breakdownByTerm'] ?? [],
            'sessionHistory' => $data['sessionHistory'] ?? [],
            'closureProjection' => $data['closureProjection'] ?? [],
            'categoryParticipation' => $data['categoryParticipation'] ?? [],
            'mostRecentSession' => $data['mostRecentSession'] ?? null,
            'sectionAverage' => $data['sectionAverage'] ?? 0,
            'evidenceCount' => $data['evidenceCount'] ?? 0,
        ]);
    }

    /**
     * Representative dashboard with student's progress.
     */
    protected function representativeDashboard(User $user, ?AcademicYear $year): Response
    {
        $yearId = $year?->id;
        $data = $this->hourAccumulator->getRepresentativeDashboard($user->id, $yearId);

        return Inertia::render('representative/dashboard', [
            'activeYear' => $year ? [
                'id' => $year->id,
                'name' => $year->name,
                'requiredHours' => (float) $year->required_hours,
            ] : null,
            'studentName' => $data['studentName'] ?? '',
            'studentId' => $data['studentId'] ?? null,
            'progress' => $data['progress'] ?? [],
            'last4WeeksTrend' => $data['last4WeeksTrend'] ?? [],
            'nextSession' => $data['nextSession'] ?? null,
            'healthReminder' => $data['healthReminder'] ?? [],
        ]);
    }
}
