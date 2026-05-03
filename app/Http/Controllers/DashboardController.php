<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\HourAccumulatorService;
use Illuminate\Http\Request;
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

        // Admin has full access
        if ($user->hasRole('admin')) {
            return $this->adminDashboard($user, $activeYear);
        }

        // Profesor sees their sections
        if ($user->hasRole('profesor')) {
            return $this->teacherDashboard($user, $activeYear);
        }

        // Alumno sees own progress
        if ($user->hasRole('alumno')) {
            return $this->studentDashboard($user, $activeYear);
        }

        // Representante sees their student's progress
        if ($user->hasRole('representante')) {
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
    protected function adminDashboard(User $user, ?AcademicYear $year): Response
    {
        $yearId = $year?->id;
        $overview = $this->hourAccumulator->getInstitutionOverview($yearId);

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
            'studentsWithNoHours' => $overview['studentsWithNoHours'] ?? [],
            'topSections' => $overview['topSections'] ?? [],
            'concerningSections' => $overview['concerningSections'] ?? [],
            'alerts' => $overview['alerts'] ?? [],
        ]);
    }

    /**
     * Teacher dashboard with section-specific data.
     */
    protected function teacherDashboard(User $user, ?AcademicYear $year): Response
    {
        $yearId = $year?->id;
        $data = $this->hourAccumulator->getTeacherDashboard($user->id, $yearId);

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
