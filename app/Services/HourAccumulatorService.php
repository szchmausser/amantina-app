<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ExternalHour;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Centralized service for calculating and aggregating student hours.
 *
 * All dashboard data flows through this service. No business logic
 * should exist in controllers or React components.
 *
 * Additive design: total_hours = jornada_hours + external_hours
 * external_hours = 0 until Hito 12 (External Hours) is implemented.
 */
class HourAccumulatorService
{
    /**
     * Get database-specific week truncation expression.
     *
     * PostgreSQL uses DATE_TRUNC('week', column).
     * SQLite uses DATE(column, 'weekday 0', '-6 days').
     *
     * @param  string  $column  The column name to truncate
     * @return string The database-specific SQL expression
     *
     * @throws \RuntimeException If the database driver is not supported
     */
    private function getWeekTruncationExpression(string $column): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'pgsql' => "DATE_TRUNC('week', {$column})",
            'sqlite' => "DATE({$column}, 'weekday 0', '-6 days')",
            default => throw new \RuntimeException("Unsupported database driver for week truncation: {$driver}"),
        };
    }

    /**
     * Calculate total hours for a student.
     *
     * @return array{
     *     jornada_hours: float,
     *     external_hours: float,
     *     total_hours: float,
     *     quota: float,
     *     percentage: float,
     *     status: 'green'|'yellow'|'red'
     * }
     */
    public function getStudentTotalHours(int $userId, ?int $academicYearId = null): array
    {
        $yearId = $this->resolveYearId($academicYearId);
        $quota = $this->getQuota($yearId);

        $jornadaHours = $this->calculateJornadaHours($userId, $yearId);

        // External hours are not tied to a specific academic year —
        // they are prior hours from another institution and always
        // contribute only to the all-time total, never to a single year.
        // Use calculateExternalHours() separately when building the all-time total.
        $externalHours = 0.0;

        $totalHours = $jornadaHours + $externalHours;
        $percentage = $quota > 0 ? ($totalHours / $quota) * 100 : 0;

        return [
            'jornada_hours' => round($jornadaHours, 2),
            'external_hours' => $externalHours,
            'total_hours' => round($totalHours, 2),
            'quota' => $quota,
            'percentage' => round($percentage, 2),
            'status' => $this->getStatusColor($percentage, $quota, $totalHours),
        ];
    }

    /**
     * Get traffic light status based on percentage and quota.
     *
     * Green:  hours >= quota AND quota > 0, OR percentage >= 80
     * Yellow: percentage >= 40 AND < 80 AND quota > 0
     * Red:    percentage < 40, OR quota = 0
     */
    public function getStatusColor(float $percentage, float $quota, float $hours): string
    {
        // Edge case: no quota defined
        if ($quota <= 0) {
            return 'red';
        }

        // Green: met quota or >= 80%
        if ($hours >= $quota || $percentage >= 80) {
            return 'green';
        }

        // Yellow: on track (40-79%)
        if ($percentage >= 40) {
            return 'yellow';
        }

        // Red: at risk (< 40%)
        return 'red';
    }

    /**
     * Get progress for all students in a section.
     *
     * @return array<int, array{
     *     student_id: int,
     *     student_name: string,
     *     jornada_hours: float,
     *     total_hours: float,
     *     quota: float,
     *     percentage: float,
     *     status: string
     * }>
     */
    public function getSectionProgress(int $sectionId, ?int $academicYearId = null): array
    {
        $yearId = $this->resolveYearId($academicYearId);
        $quota = $this->getQuota($yearId);

        $students = DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('enrollments.section_id', $sectionId)
            ->whereNull('enrollments.deleted_at')
            ->whereNull('users.deleted_at')
            ->select('users.id as student_id', 'users.name')
            ->get();

        $result = [];

        foreach ($students as $student) {
            $jornadaHours = $this->calculateJornadaHours($student->student_id, $yearId);
            $totalHours = $jornadaHours;
            $percentage = $quota > 0 ? ($totalHours / $quota) * 100 : 0;

            $result[$student->student_id] = [
                'student_id' => $student->student_id,
                'student_name' => $student->name,
                'jornada_hours' => round($jornadaHours, 2),
                'total_hours' => round($totalHours, 2),
                'quota' => $quota,
                'percentage' => round($percentage, 2),
                'status' => $this->getStatusColor($percentage, $quota, $totalHours),
            ];
        }

        return $result;
    }

    /**
     * Get institution-wide overview for admin dashboard.
     */
    public function getInstitutionOverview(?int $academicYearId = null): array
    {
        $yearId = $this->resolveYearId($academicYearId);
        $quota = $this->getQuota($yearId);

        // Global compliance with detailed student data
        $allStudents = DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->whereNull('enrollments.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('sections.deleted_at')
            ->whereNull('grades.deleted_at')
            ->select(
                'users.id as student_id',
                'users.name as student_name',
                'sections.name as section_name',
                'grades.name as grade_name'
            )
            ->distinct()
            ->get();

        $metQuota = 0;
        $onTrack = 0;
        $atRisk = 0;
        $noHours = 0;
        $totalHoursAll = 0;

        $outstandingStudents = [];
        $atRiskStudents = [];
        $studentsWithNoHours = [];
        $onTrackStudents = [];
        $inProgressStudents = [];

        foreach ($allStudents as $student) {
            $hours = $this->calculateJornadaHours($student->student_id, $yearId);
            $totalHoursAll += $hours;
            $percentage = $quota > 0 ? ($hours / $quota) * 100 : 0;
            $status = $this->getStatusColor($percentage, $quota, $hours);

            $studentData = [
                'id' => $student->student_id,
                'name' => $student->student_name,
                'hours' => round($hours, 2),
                'percentage' => round($percentage, 2),
                'section' => $student->section_name,
                'grade' => $student->grade_name,
                'status' => $status,
            ];

            // Categorize students
            if ($hours == 0) {
                $noHours++;
                $studentsWithNoHours[] = $studentData;
            } elseif ($percentage >= 100) {
                $metQuota++;
                $outstandingStudents[] = $studentData;
            } elseif ($percentage >= 80) {
                $metQuota++;
                $onTrackStudents[] = $studentData;
            } elseif ($percentage >= 40) {
                $onTrack++;
                $inProgressStudents[] = $studentData;
            } else {
                $atRisk++;
                $atRiskStudents[] = $studentData;
            }
        }

        // Sort outstanding students by percentage descending
        usort($outstandingStudents, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        // Sort at-risk students by percentage ascending
        usort($atRiskStudents, fn ($a, $b) => $a['percentage'] <=> $b['percentage']);

        // Calculate general ranking of students by hours (excluding zero hours)
        $topStudents = array_merge($outstandingStudents, $onTrackStudents, $inProgressStudents, $atRiskStudents);
        usort($topStudents, fn ($a, $b) => $b['hours'] <=> $a['hours']);

        $totalStudents = $allStudents->count();
        $globalPercentage = $totalStudents > 0 ? ($metQuota / $totalStudents) * 100 : 0;
        $averageHours = $totalStudents > 0 ? $totalHoursAll / $totalStudents : 0;

        // Section ranking with distribution
        $sections = DB::table('sections')
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->whereNull('sections.deleted_at')
            ->whereNull('grades.deleted_at')
            ->select(
                'sections.id',
                'sections.name as section_name',
                'grades.name as grade_name'
            )
            ->get();

        $allSectionsData = [];

        foreach ($sections as $section) {
            $students = $this->getSectionProgress($section->id, $yearId);
            if (empty($students)) {
                continue;
            }

            // Get all teachers assigned to this section
            $teachers = DB::table('teacher_assignments')
                ->join('users', 'teacher_assignments.user_id', '=', 'users.id')
                ->where('teacher_assignments.section_id', $section->id)
                ->whereNull('teacher_assignments.deleted_at')
                ->whereNull('users.deleted_at')
                ->when($yearId, fn ($q) => $q->where('teacher_assignments.academic_year_id', $yearId))
                ->select('users.id', 'users.name')
                ->get()
                ->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                    ];
                })
                ->toArray();

            $avgProgress = array_sum(array_column($students, 'percentage')) / count($students);

            // Count distribution
            $distribution = [
                'onTrack' => 0,
                'inProgress' => 0,
                'atRisk' => 0,
            ];

            $sectionOnTrackStudents = [];
            $sectionInProgressStudents = [];
            $sectionAtRiskStudents = [];

            foreach ($students as $student) {
                $studentData = [
                    'id' => $student['student_id'],
                    'name' => $student['student_name'],
                    'hours' => $student['total_hours'],
                    'percentage' => $student['percentage'],
                ];

                if ($student['percentage'] >= 80) {
                    $distribution['onTrack']++;
                    $sectionOnTrackStudents[] = $studentData;
                } elseif ($student['percentage'] >= 40) {
                    $distribution['inProgress']++;
                    $sectionInProgressStudents[] = $studentData;
                } else {
                    $distribution['atRisk']++;
                    $sectionAtRiskStudents[] = $studentData;
                }
            }

            $allSectionsData[] = [
                'id' => $section->id,
                'name' => $section->section_name,
                'grade' => $section->grade_name,
                'teachers' => $teachers, // Array of teacher names
                'avgPercentage' => round($avgProgress, 2),
                'studentCount' => count($students),
                'distribution' => $distribution,
                'onTrackStudents' => $sectionOnTrackStudents,
                'inProgressStudents' => $sectionInProgressStudents,
                'atRiskStudents' => $sectionAtRiskStudents,
            ];
        }

        // Sort all sections by average percentage
        usort($allSectionsData, fn ($a, $b) => $b['avgPercentage'] <=> $a['avgPercentage']);

        // Top 3 sections (best performing)
        $topSections = array_slice($allSectionsData, 0, 3);

        // Bottom 3 sections (need most attention) - reverse order so worst is first
        $concerningSections = array_slice(array_reverse($allSectionsData), 0, 3);

        // Alerts: zero-hour students (now we have the list)
        $zeroHourStudents = count($studentsWithNoHours);

        // Sessions without attendance (only realized sessions)
        $sessionsWithoutAttendanceList = DB::table('field_sessions')
            ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
            ->leftJoin('attendances', function ($join) {
                $join->on('field_sessions.id', '=', 'attendances.field_session_id')
                    ->whereNull('attendances.deleted_at');
            })
            ->leftJoin('users as teachers', 'field_sessions.user_id', '=', 'teachers.id')
            ->whereNull('field_sessions.deleted_at')
            ->where('field_session_statuses.name', 'realized')
            ->whereNull('attendances.id')
            ->select(
                'field_sessions.id',
                'field_sessions.name',
                'field_sessions.start_datetime',
                'field_sessions.location_name',
                'teachers.name as teacher_name'
            )
            ->orderBy('field_sessions.start_datetime', 'desc')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'name' => $session->name,
                'date' => $session->start_datetime,
                'location' => $session->location_name,
                'teacher' => $session->teacher_name,
            ])
            ->toArray();

        $sessionsWithoutAttendance = count($sessionsWithoutAttendanceList);

        // Sessions with attendance but no activities (critical anomaly)
        $sessionsWithAttendanceNoActivitiesList = DB::table('field_sessions')
            ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
            ->join('attendances', function ($join) {
                $join->on('field_sessions.id', '=', 'attendances.field_session_id')
                    ->where('attendances.attended', true)
                    ->whereNull('attendances.deleted_at');
            })
            ->leftJoin('attendance_activities', function ($join) {
                $join->on('attendances.id', '=', 'attendance_activities.attendance_id')
                    ->whereNull('attendance_activities.deleted_at');
            })
            ->leftJoin('users as teachers', 'field_sessions.user_id', '=', 'teachers.id')
            ->whereNull('field_sessions.deleted_at')
            ->where('field_session_statuses.name', 'realized')
            ->whereNull('attendance_activities.id')
            ->select(
                'field_sessions.id',
                'field_sessions.name',
                'field_sessions.start_datetime',
                'field_sessions.location_name',
                'teachers.name as teacher_name',
                DB::raw('COUNT(DISTINCT attendances.id) as attendance_count')
            )
            ->groupBy('field_sessions.id', 'field_sessions.name', 'field_sessions.start_datetime', 'field_sessions.location_name', 'teachers.name')
            ->orderBy('field_sessions.start_datetime', 'desc')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'name' => $session->name,
                'date' => $session->start_datetime,
                'location' => $session->location_name,
                'teacher' => $session->teacher_name,
                'attendanceCount' => $session->attendance_count,
            ])
            ->toArray();

        $sessionsWithAttendanceNoActivities = count($sessionsWithAttendanceNoActivitiesList);

        // Attendances marked present but with 0 hours (critical anomaly)
        $attendancesWithZeroHoursList = DB::table('attendances')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
            ->join('users as students', 'attendances.user_id', '=', 'students.id')
            ->join('enrollments', 'students.id', '=', 'enrollments.user_id')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->leftJoin('users as teachers', 'field_sessions.user_id', '=', 'teachers.id')
            ->leftJoin('attendance_activities', function ($join) {
                $join->on('attendances.id', '=', 'attendance_activities.attendance_id')
                    ->whereNull('attendance_activities.deleted_at');
            })
            ->where('attendances.attended', true)
            ->where('field_session_statuses.name', 'realized')
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('enrollments.deleted_at')
            ->select(
                'attendances.id',
                'students.id as student_id',
                'students.name as student_name',
                'sections.name as section_name',
                'grades.name as grade_name',
                'field_sessions.id as session_id',
                'field_sessions.name as session_name',
                'field_sessions.start_datetime',
                'teachers.name as teacher_name',
                DB::raw('COALESCE(SUM(attendance_activities.hours), 0) as total_hours')
            )
            ->groupBy(
                'attendances.id',
                'students.id',
                'students.name',
                'sections.name',
                'grades.name',
                'field_sessions.id',
                'field_sessions.name',
                'field_sessions.start_datetime',
                'teachers.name'
            )
            ->having(DB::raw('COALESCE(SUM(attendance_activities.hours), 0)'), '=', 0)
            ->orderBy('field_sessions.start_datetime', 'desc')
            ->get()
            ->map(fn ($attendance) => [
                'id' => $attendance->id,
                'studentId' => $attendance->student_id,
                'studentName' => $attendance->student_name,
                'section' => $attendance->section_name,
                'grade' => $attendance->grade_name,
                'sessionId' => $attendance->session_id,
                'sessionName' => $attendance->session_name,
                'sessionDate' => $attendance->start_datetime,
                'teacher' => $attendance->teacher_name,
            ])
            ->toArray();

        $attendancesWithZeroHours = count($attendancesWithZeroHoursList);

        return [
            'totalStudents' => $totalStudents,
            'requiredHours' => $quota,
            'averageHours' => round($averageHours, 2),
            'distribution' => [
                'onTrack' => $metQuota,
                'inProgress' => $onTrack,
                'atRisk' => $atRisk,
                'noHours' => $noHours,
            ],
            'onTrackStudents' => $onTrackStudents,
            'inProgressStudents' => $inProgressStudents,
            'atRiskStudents' => $atRiskStudents,
            'outstandingStudents' => $outstandingStudents,
            'topStudents' => $topStudents,
            'studentsWithNoHours' => $studentsWithNoHours,
            'topSections' => $topSections,
            'concerningSections' => $concerningSections,
            'alerts' => [
                'zeroHourStudents' => $zeroHourStudents,
                'sessionsWithoutAttendance' => $sessionsWithoutAttendance,
                'sessionsWithoutAttendanceList' => $sessionsWithoutAttendanceList,
                'sessionsWithAttendanceNoActivities' => $sessionsWithAttendanceNoActivities,
                'sessionsWithAttendanceNoActivitiesList' => $sessionsWithAttendanceNoActivitiesList,
                'attendancesWithZeroHours' => $attendancesWithZeroHours,
                'attendancesWithZeroHoursList' => $attendancesWithZeroHoursList,
            ],
        ];
    }

    /**
     * Get category distribution for admin dashboard (all teachers, optional grade/section filters).
     *
     * @return array<int, array{categoryName: string, totalHours: float, count: int, minRequiredHours: null, students: array}>
     */
    public function getAdminCategoryDistribution(?int $yearId = null, ?int $gradeId = null, ?int $sectionId = null, ?int $teacherId = null): array
    {
        // Per-student breakdown per category
        $categoryStudents = DB::table('attendance_activities')
            ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->join('activity_categories', 'attendance_activities.activity_category_id', '=', 'activity_categories.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->join('enrollments', function ($join) use ($yearId) {
                $join->on('users.id', '=', 'enrollments.user_id')
                    ->whereNull('enrollments.deleted_at');
                if ($yearId) {
                    $join->where('enrollments.academic_year_id', $yearId);
                }
            })
            ->join('sections as sec', 'enrollments.section_id', '=', 'sec.id')
            ->join('grades', 'sec.grade_id', '=', 'grades.id')
            ->where('attendances.attended', true)
            ->whereNull('attendance_activities.deleted_at')
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('activity_categories.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('sec.deleted_at')
            ->whereNull('grades.deleted_at')
            ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
            ->when($gradeId, fn ($q) => $q->where('sec.grade_id', $gradeId))
            ->when($sectionId, fn ($q) => $q->where('enrollments.section_id', $sectionId))
            ->when($teacherId, fn ($q) => $q->where('field_sessions.user_id', $teacherId))
            ->select(
                'activity_categories.name as category_name',
                'users.id as student_id',
                DB::raw('users.name as student_name'),
                DB::raw('sec.name as section_name'),
                DB::raw('grades.name as grade_name'),
                DB::raw('SUM(attendance_activities.hours) as hours')
            )
            ->groupBy('activity_categories.name', 'users.id', 'users.name', 'sec.name', 'grades.name')
            ->orderBy('activity_categories.name')
            ->orderByDesc(DB::raw('SUM(attendance_activities.hours)'))
            ->get();

        // Group per-student results by category name
        $studentsByCategory = [];
        foreach ($categoryStudents as $row) {
            $studentsByCategory[$row->category_name][] = [
                'studentId' => $row->student_id,
                'studentName' => $row->student_name,
                'sectionName' => $row->section_name ?? '',
                'gradeName' => $row->grade_name ?? '',
                'hours' => round((float) $row->hours, 2),
            ];
        }

        // Category distribution across ALL teachers
        $categoryDistribution = DB::table('attendance_activities')
            ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->join('activity_categories', 'attendance_activities.activity_category_id', '=', 'activity_categories.id')
            ->join('enrollments', function ($join) use ($yearId) {
                $join->on('attendances.user_id', '=', 'enrollments.user_id')
                    ->whereNull('enrollments.deleted_at');
                if ($yearId) {
                    $join->where('enrollments.academic_year_id', $yearId);
                }
            })
            ->join('sections as sec', 'enrollments.section_id', '=', 'sec.id')
            ->where('attendances.attended', true)
            ->whereNull('attendance_activities.deleted_at')
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('activity_categories.deleted_at')
            ->whereNull('sec.deleted_at')
            ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
            ->when($gradeId, fn ($q) => $q->where('sec.grade_id', $gradeId))
            ->when($sectionId, fn ($q) => $q->where('enrollments.section_id', $sectionId))
            ->when($teacherId, fn ($q) => $q->where('field_sessions.user_id', $teacherId))
            ->select(
                'activity_categories.name as category_name',
                DB::raw('SUM(attendance_activities.hours) as total_hours'),
                DB::raw('COUNT(DISTINCT attendances.id) as attendance_count'),
                DB::raw('COUNT(DISTINCT field_sessions.id) as session_count')
            )
            ->groupBy('activity_categories.name')
            ->orderByDesc('total_hours')
            ->get()
            ->map(fn ($r) => [
                'categoryName' => $r->category_name,
                'totalHours' => round((float) $r->total_hours, 2),
                'count' => (int) $r->attendance_count,
                'sessionCount' => (int) $r->session_count,
                'minRequiredHours' => null,
                'students' => collect($studentsByCategory[$r->category_name] ?? [])
                    ->map(fn ($s) => [
                        'studentId' => $s['studentId'],
                        'studentName' => $s['studentName'],
                        'sectionName' => $s['sectionName'],
                        'gradeName' => $s['gradeName'],
                        'hours' => $s['hours'],
                        'percentage' => $r->total_hours > 0 ? round(($s['hours'] / (float) $r->total_hours) * 100, 1) : 0,
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->toArray();

        return $categoryDistribution;
    }

    /**
     * Get the section IDs assigned to a teacher (excluding soft-deleted assignments).
     *
     * @return Collection<int, int>
     */
    public function getTeacherSectionIds(int $teacherId): Collection
    {
        return DB::table('teacher_assignments')
            ->where('user_id', $teacherId)
            ->whereNull('deleted_at')
            ->pluck('section_id');
    }

    /**
     * Get student distribution overview for a teacher's assigned sections.
     *
     * Mirrors getInstitutionOverview() but scoped to the teacher's sections.
     *
     * @return array{
     *     totalStudents: int,
     *     distribution: array{onTrack: int, inProgress: int, atRisk: int, zeroHours: int},
     *     onTrackStudents: list<array{id: int, name: string, sectionName: string, gradeName: string, hours: float, quota: float, percentage: float, status: string}>,
     *     inProgressStudents: list<array{...}>,
     *     atRiskStudents: list<array{...}>,
     *     outstandingStudents: list<array{...}>,
     *     topStudents: list<array{...}>,
     *     studentsWithNoHours: list<array{...}>,
     * }
     */
    public function getTeacherStudentDistribution(int $teacherId, ?int $yearId): array
    {
        $yearId = $this->resolveYearId($yearId);
        $quota = $this->getQuota($yearId);
        $sectionIds = $this->getTeacherSectionIds($teacherId);

        if ($sectionIds->isEmpty()) {
            return [
                'totalStudents' => 0,
                'distribution' => ['onTrack' => 0, 'inProgress' => 0, 'atRisk' => 0, 'zeroHours' => 0],
                'onTrackStudents' => [],
                'inProgressStudents' => [],
                'atRiskStudents' => [],
                'outstandingStudents' => [],
                'topStudents' => [],
                'studentsWithNoHours' => [],
            ];
        }

        // Get all students enrolled in the teacher's sections
        $allStudents = DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->whereIn('enrollments.section_id', $sectionIds)
            ->when($yearId, fn ($q) => $q->where('enrollments.academic_year_id', $yearId))
            ->whereNull('enrollments.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('sections.deleted_at')
            ->whereNull('grades.deleted_at')
            ->select(
                'users.id as student_id',
                'users.name as student_name',
                'sections.name as section_name',
                'grades.name as grade_name'
            )
            ->distinct()
            ->get();

        $onTrackCount = 0;
        $inProgressCount = 0;
        $atRiskCount = 0;
        $zeroHoursCount = 0;

        $onTrackStudents = [];
        $inProgressStudents = [];
        $atRiskStudents = [];
        $outstandingStudents = [];
        $studentsWithNoHours = [];

        foreach ($allStudents as $student) {
            $hours = $this->calculateJornadaHours($student->student_id, $yearId);
            $percentage = $quota > 0 ? ($hours / $quota) * 100 : 0;
            $status = $this->getStatusColor($percentage, $quota, $hours);

            $studentData = [
                'id' => (int) $student->student_id,
                'name' => $student->student_name,
                'sectionName' => $student->section_name,
                'gradeName' => $student->grade_name,
                'hours' => round($hours, 2),
                'quota' => $quota,
                'percentage' => round($percentage, 2),
                'status' => $status,
            ];

            if ($hours == 0) {
                $zeroHoursCount++;
                $studentsWithNoHours[] = $studentData;
            } elseif ($percentage >= 100) {
                $onTrackCount++;
                $outstandingStudents[] = $studentData;
            } elseif ($percentage >= 80) {
                $onTrackCount++;
                $onTrackStudents[] = $studentData;
            } elseif ($percentage >= 40) {
                $inProgressCount++;
                $inProgressStudents[] = $studentData;
            } else {
                $atRiskCount++;
                $atRiskStudents[] = $studentData;
            }
        }

        // Sort outstanding by hours descending
        usort($outstandingStudents, fn ($a, $b) => $b['hours'] <=> $a['hours']);

        // Sort at-risk by percentage ascending (worst first)
        usort($atRiskStudents, fn ($a, $b) => $a['percentage'] <=> $b['percentage']);

        // Build top students: all students with hours, sorted by hours descending
        $topStudents = array_merge($outstandingStudents, $onTrackStudents, $inProgressStudents, $atRiskStudents);
        usort($topStudents, fn ($a, $b) => $b['hours'] <=> $a['hours']);

        $totalStudents = $allStudents->count();

        return [
            'totalStudents' => $totalStudents,
            'distribution' => [
                'onTrack' => $onTrackCount,
                'inProgress' => $inProgressCount,
                'atRisk' => $atRiskCount,
                'zeroHours' => $zeroHoursCount,
            ],
            'onTrackStudents' => array_values($onTrackStudents),
            'inProgressStudents' => array_values($inProgressStudents),
            'atRiskStudents' => array_values($atRiskStudents),
            'outstandingStudents' => array_values($outstandingStudents),
            'topStudents' => array_values($topStudents),
            'studentsWithNoHours' => array_values($studentsWithNoHours),
        ];
    }

    /**
     * Get upcoming field sessions for a teacher.
     *
     * Returns future sessions ordered by date ascending, limited to 10.
     * Includes session name, date, location, status name, and section name.
     *
     * NOTE: field_sessions does not have a section_id FK. Section name is
     * resolved via teacher_assignments. For multi-section teachers, the
     * first section alphabetically is returned.
     *
     * @return list<array{id: int, name: string, date: string, location: string, statusName: string, sectionName: string}>
     */
    public function getTeacherUpcomingSessions(int $teacherId, ?int $yearId): array
    {
        $sectionIds = $this->getTeacherSectionIds($teacherId);

        if ($sectionIds->isEmpty()) {
            return [];
        }

        return DB::table('field_sessions')
            ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
            ->join('teacher_assignments', function ($join) {
                $join->on('field_sessions.user_id', '=', 'teacher_assignments.user_id')
                    ->on('field_sessions.academic_year_id', '=', 'teacher_assignments.academic_year_id')
                    ->whereNull('teacher_assignments.deleted_at');
            })
            ->join('sections', 'teacher_assignments.section_id', '=', 'sections.id')
            ->where('field_sessions.user_id', $teacherId)
            ->where('field_sessions.start_datetime', '>=', now())
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('sections.deleted_at')
            ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
            ->select(
                'field_sessions.id',
                'field_sessions.name',
                'field_sessions.start_datetime as date',
                'field_sessions.location_name as location',
                'field_session_statuses.name as status_name',
                DB::raw('MIN(sections.name) as section_name')
            )
            ->groupBy(
                'field_sessions.id',
                'field_sessions.name',
                'field_sessions.start_datetime',
                'field_sessions.location_name',
                'field_session_statuses.name'
            )
            ->orderBy('field_sessions.start_datetime')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'date' => $r->date,
                'location' => $r->location,
                'statusName' => $r->status_name,
                'sectionName' => $r->section_name,
            ])
            ->toArray();
    }

    /**
     * Get teacher-specific dashboard data.
     */
    public function getTeacherDashboard(int $teacherId, ?int $academicYearId = null, ?int $gradeId = null, ?int $sectionId = null): array
    {
        $yearId = $this->resolveYearId($academicYearId);
        $quota = $this->getQuota($yearId);

        // Sections assigned to this teacher
        $assignedSections = DB::table('teacher_assignments')
            ->join('sections', 'teacher_assignments.section_id', '=', 'sections.id')
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->where('teacher_assignments.user_id', $teacherId)
            ->whereNull('teacher_assignments.deleted_at')
            ->whereNull('sections.deleted_at')
            ->whereNull('grades.deleted_at')
            ->select('sections.id as section_id', 'sections.name as section_name', 'grades.name as grade_name')
            ->get();

        $sections = [];
        foreach ($assignedSections as $section) {
            $sectionStudents = $this->getSectionProgress($section->section_id, $yearId);
            $studentList = array_values($sectionStudents);

            // Transform to match frontend's StudentProgress structure (nested hours object)
            $formattedStudents = array_map(fn ($s) => [
                'studentId' => $s['student_id'],
                'studentName' => $s['student_name'],
                'hours' => [
                    'jornadaHours' => $s['jornada_hours'],
                    'externalHours' => 0,
                    'totalHours' => $s['total_hours'],
                    'quota' => $s['quota'],
                    'percentage' => $s['percentage'],
                    'status' => $s['status'],
                ],
            ], $studentList);

            $avgProgress = count($studentList) > 0
                ? array_sum(array_column($studentList, 'percentage')) / count($studentList)
                : 0;

            $sections[] = [
                'sectionId' => $section->section_id,
                'sectionName' => $section->section_name,
                'gradeName' => $section->grade_name,
                'averageProgress' => round($avgProgress, 2),
                'studentCount' => count($studentList),
                'students' => $formattedStudents,
            ];
        }

        // Own sessions stats
        $ownSessions = DB::table('field_sessions')
            ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
            ->where('field_sessions.user_id', $teacherId)
            ->whereNull('field_sessions.deleted_at')
            ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN field_session_statuses.name = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN field_session_statuses.name = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw('SUM(field_sessions.base_hours) as total_hours_generated')
            )
            ->first();

        // Pending attendance: completed sessions without attendance records
        $pendingAttendance = DB::table('field_sessions')
            ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
            ->leftJoin('attendances', function ($join) {
                $join->on('attendances.field_session_id', '=', 'field_sessions.id')
                    ->whereNull('attendances.deleted_at');
            })
            ->where('field_sessions.user_id', $teacherId)
            ->where('field_session_statuses.name', 'completed')
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('attendances.id')
            ->count();

        // Low attendance students (students with fewer than 3 attendances in current term)
        $lowAttendanceStudents = DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->leftJoin('attendances', function ($join) use ($teacherId) {
                $join->on('attendances.user_id', '=', 'users.id')
                    ->join('field_sessions as fs', 'attendances.field_session_id', '=', 'fs.id')
                    ->where('fs.user_id', $teacherId)
                    ->whereNull('attendances.deleted_at')
                    ->whereNull('fs.deleted_at');
            })
            ->leftJoin('attendance_activities', function ($join) {
                $join->on('attendance_activities.attendance_id', '=', 'attendances.id')
                    ->whereNull('attendance_activities.deleted_at');
            })
            ->whereIn('enrollments.section_id', $assignedSections->pluck('section_id'))
            ->whereNull('enrollments.deleted_at')
            ->whereNull('users.deleted_at')
            ->where('attendances.attended', true)
            ->select(
                'users.id as student_id',
                DB::raw('users.name as student_name'),
                'sections.name as section_name',
                'grades.name as grade_name',
                'enrollments.section_id',
                DB::raw('COUNT(DISTINCT attendances.id) as attendance_count'),
                DB::raw('COALESCE(SUM(attendance_activities.hours), 0) as total_hours')
            )
            ->groupBy('users.id', 'users.name', 'sections.name', 'grades.name', 'enrollments.section_id')
            ->having(DB::raw('COUNT(DISTINCT attendances.id)'), '<', 3)
            ->orderBy(DB::raw('COUNT(DISTINCT attendances.id)'))
            ->get()
            ->map(fn ($r) => [
                'studentId' => $r->student_id,
                'studentName' => $r->student_name,
                'sectionName' => $r->section_name,
                'gradeName' => $r->grade_name,
                'sectionId' => $r->section_id,
                'attendanceCount' => $r->attendance_count,
                'totalHours' => round((float) $r->total_hours, 2),
            ])
            ->toArray();

        // Per-student breakdown per category (separate query to avoid groupBy conflicts)
        $categoryStudents = DB::table('attendance_activities')
            ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->join('activity_categories', 'attendance_activities.activity_category_id', '=', 'activity_categories.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->leftJoin('enrollments', function ($join) use ($yearId) {
                $join->on('users.id', '=', 'enrollments.user_id')
                    ->whereNull('enrollments.deleted_at');
                if ($yearId) {
                    $join->where('enrollments.academic_year_id', $yearId);
                }
            })
            ->leftJoin('sections as sec', 'enrollments.section_id', '=', 'sec.id')
            ->leftJoin('grades', 'sec.grade_id', '=', 'grades.id')
            ->where('field_sessions.user_id', $teacherId)
            ->where('attendances.attended', true)
            ->whereNull('attendance_activities.deleted_at')
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('activity_categories.deleted_at')
            ->whereNull('users.deleted_at')
            ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
            ->when($gradeId, fn ($q) => $q->where('grades.id', $gradeId))
            ->when($sectionId, fn ($q) => $q->where('sec.id', $sectionId))
            ->select(
                'activity_categories.name as category_name',
                'users.id as student_id',
                DB::raw('users.name as student_name'),
                DB::raw('MAX(sec.name) as section_name'),
                DB::raw('MAX(grades.name) as grade_name'),
                DB::raw('SUM(attendance_activities.hours) as hours')
            )
            ->groupBy('activity_categories.name', 'users.id', 'users.name')
            ->orderBy('activity_categories.name')
            ->orderByDesc(DB::raw('SUM(attendance_activities.hours)'))
            ->get();

        // Group per-student results by category name
        $studentsByCategory = [];
        foreach ($categoryStudents as $row) {
            $studentsByCategory[$row->category_name][] = [
                'studentId' => $row->student_id,
                'studentName' => $row->student_name,
                'sectionName' => $row->section_name ?? '',
                'gradeName' => $row->grade_name ?? '',
                'hours' => round((float) $row->hours, 2),
            ];
        }

        // Category distribution in own sessions
        $categoryDistribution = DB::table('attendance_activities')
            ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->join('activity_categories', 'attendance_activities.activity_category_id', '=', 'activity_categories.id')
            ->join('enrollments', function ($join) use ($yearId) {
                $join->on('attendances.user_id', '=', 'enrollments.user_id')
                    ->whereNull('enrollments.deleted_at');
                if ($yearId) {
                    $join->where('enrollments.academic_year_id', $yearId);
                }
            })
            ->join('sections as sec', 'enrollments.section_id', '=', 'sec.id')
            ->where('field_sessions.user_id', $teacherId)
            ->where('attendances.attended', true)
            ->whereNull('attendance_activities.deleted_at')
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('activity_categories.deleted_at')
            ->whereNull('sec.deleted_at')
            ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
            ->when($gradeId, fn ($q) => $q->where('sec.grade_id', $gradeId))
            ->when($sectionId, fn ($q) => $q->where('enrollments.section_id', $sectionId))
            ->select(
                'activity_categories.name as category_name',
                DB::raw('SUM(attendance_activities.hours) as total_hours'),
                DB::raw('COUNT(DISTINCT attendances.id) as attendance_count'),
                DB::raw('COUNT(DISTINCT field_sessions.id) as session_count')
            )
            ->groupBy('activity_categories.name')
            ->orderByDesc('total_hours')
            ->get()
            ->map(fn ($r) => [
                'categoryName' => $r->category_name,
                'totalHours' => round((float) $r->total_hours, 2),
                'count' => (int) $r->attendance_count,
                'sessionCount' => (int) $r->session_count,
                'minRequiredHours' => null, // activity_categories has no min_required_hours column
                'students' => collect($studentsByCategory[$r->category_name] ?? [])
                    ->map(fn ($s) => [
                        'studentId' => $s['studentId'],
                        'studentName' => $s['studentName'],
                        'sectionName' => $s['sectionName'],
                        'gradeName' => $s['gradeName'],
                        'hours' => $s['hours'],
                        'percentage' => $r->total_hours > 0 ? round(($s['hours'] / (float) $r->total_hours) * 100, 1) : 0,
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->toArray();

        // Sessions per term
        $sessionsPerTerm = DB::table('field_sessions')
            ->join('school_terms', 'field_sessions.school_term_id', '=', 'school_terms.id')
            ->where('field_sessions.user_id', $teacherId)
            ->whereNull('field_sessions.deleted_at')
            ->whereNull('school_terms.deleted_at')
            ->when($yearId, fn ($q) => $q->where('field_sessions.academic_year_id', $yearId))
            ->select(
                'school_terms.term_type_name',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('school_terms.term_type_name')
            ->get()
            ->map(fn ($r) => [
                'termName' => $r->term_type_name,
                'count' => $r->count,
            ])
            ->toArray();

        // Health reminders: students with health conditions who attended recent sessions
        $healthReminders = DB::table('student_health_records')
            ->join('users', 'student_health_records.user_id', '=', 'users.id')
            ->join('health_conditions', 'student_health_records.health_condition_id', '=', 'health_conditions.id')
            ->join('attendances', 'student_health_records.user_id', '=', 'attendances.user_id')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->where('field_sessions.user_id', $teacherId)
            ->whereNull('student_health_records.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('health_conditions.deleted_at')
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->where('attendances.attended', true)
            ->select(
                'users.id as student_id',
                DB::raw('users.name as student_name'),
                'health_conditions.name as condition_name',
                DB::raw('MAX(field_sessions.start_datetime) as last_session_date')
            )
            ->groupBy('users.id', 'users.name', 'health_conditions.name')
            ->orderByDesc('last_session_date')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                $lastSessionDate = $r->last_session_date ? Carbon::parse($r->last_session_date) : null;
                $daysSince = $lastSessionDate ? (int) $lastSessionDate->diffInDays(now()) : 0;

                return [
                    'studentId' => $r->student_id,
                    'studentName' => $r->student_name,
                    'conditionName' => $r->condition_name,
                    'severity' => 'medium', // Default: health_conditions table has no severity column
                    'lastSessionDate' => $r->last_session_date,
                    'daysSinceLastSession' => $daysSince,
                ];
            })
            ->toArray();

        return [
            'sections' => $sections,
            'ownSessions' => [
                'total' => $ownSessions->total ?? 0,
                'completed' => $ownSessions->completed ?? 0,
                'cancelled' => $ownSessions->cancelled ?? 0,
                'totalHoursGenerated' => round((float) ($ownSessions->total_hours_generated ?? 0), 2),
            ],
            'pendingAttendance' => $pendingAttendance,
            'lowAttendanceStudents' => $lowAttendanceStudents,
            'categoryDistribution' => $categoryDistribution,
            'sessionsPerTerm' => $sessionsPerTerm,
            'healthReminders' => $healthReminders,
        ];
    }

    /**
     * Get student-specific dashboard data.
     */
    public function getStudentDashboard(int $studentId, ?int $academicYearId = null): array
    {
        $yearId = $this->resolveYearId($academicYearId);
        $quota = $this->getQuota($yearId);

        // Progress
        $progress = $this->getStudentTotalHours($studentId, $yearId);

        // Breakdown by year
        $years = AcademicYear::currentAndPast()->orderBy('start_date')->get();
        $breakdownByYear = [];
        foreach ($years as $year) {
            $hours = $this->calculateJornadaHours($studentId, $year->id);
            $breakdownByYear[] = [
                'yearName' => $year->name,
                'totalHours' => round($hours, 2),
                'quota' => (float) $year->required_hours,
            ];
        }

        // Breakdown by term
        $breakdownByTerm = [];
        if ($yearId !== null) {
            $terms = DB::table('school_terms')
                ->where('academic_year_id', $yearId)
                ->whereNull('deleted_at')
                ->select('id', 'term_type_name')
                ->get();

            foreach ($terms as $term) {
                $hours = DB::table('attendance_activities')
                    ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
                    ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
                    ->where('attendances.user_id', $studentId)
                    ->where('field_sessions.school_term_id', $term->id)
                    ->where('attendances.attended', true)
                    ->whereNull('attendance_activities.deleted_at')
                    ->whereNull('attendances.deleted_at')
                    ->whereNull('field_sessions.deleted_at')
                    ->sum('attendance_activities.hours');

                $breakdownByTerm[] = [
                    'termName' => $term->term_type_name,
                    'totalHours' => round((float) $hours, 2),
                ];
            }
        }

        // Session history
        $sessions = DB::table('attendances')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->where('attendances.user_id', $studentId)
            ->where('attendances.attended', true)
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->select(
                'attendances.id as attendance_id',
                'field_sessions.name as session_name',
                'field_sessions.start_datetime as date',
                'field_sessions.location_name as location',
                DB::raw('(SELECT SUM(hours) FROM attendance_activities WHERE attendance_id = attendances.id AND deleted_at IS NULL) as hours')
            )
            ->orderByDesc('field_sessions.start_datetime')
            ->limit(20)
            ->get();

        // Get activities for these attendances
        $attendanceIds = $sessions->pluck('attendance_id')->toArray();
        $activities = DB::table('attendance_activities')
            ->join('activity_categories', 'attendance_activities.activity_category_id', '=', 'activity_categories.id')
            ->whereIn('attendance_activities.attendance_id', $attendanceIds)
            ->whereNull('attendance_activities.deleted_at')
            ->select(
                'attendance_activities.attendance_id',
                'activity_categories.name as category_name',
                'attendance_activities.hours'
            )
            ->get()
            ->groupBy('attendance_id');

        $sessionHistory = $sessions
            ->map(fn ($r) => [
                'sessionName' => $r->session_name,
                'date' => $r->date,
                'location' => $r->location,
                'hours' => round((float) ($r->hours ?? 0), 2),
                'activities' => ($activities->get($r->attendance_id) ?? collect())
                    ->map(fn ($a) => [
                        'categoryName' => $a->category_name,
                        'hours' => round((float) $a->hours, 2),
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->toArray();

        // Closure projection
        $closureProjection = $this->calculateClosureProjection($studentId, $quota, $progress['total_hours']);

        // Category participation
        $categoryParticipation = DB::table('attendance_activities')
            ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
            ->join('activity_categories', 'attendance_activities.activity_category_id', '=', 'activity_categories.id')
            ->where('attendances.user_id', $studentId)
            ->where('attendances.attended', true)
            ->whereNull('attendance_activities.deleted_at')
            ->whereNull('attendances.deleted_at')
            ->whereNull('activity_categories.deleted_at')
            ->when($yearId, fn ($q) => $q->where('attendances.academic_year_id', $yearId))
            ->select(
                'activity_categories.name as category_name',
                DB::raw('COUNT(attendance_activities.id) as count'),
                DB::raw('SUM(attendance_activities.hours) as total_hours')
            )
            ->groupBy('activity_categories.name')
            ->orderByDesc('total_hours')
            ->get()
            ->map(fn ($r) => [
                'categoryName' => $r->category_name,
                'count' => $r->count,
                'totalHours' => round((float) $r->total_hours, 2),
            ])
            ->toArray();

        // Most recent session
        $mostRecentSession = DB::table('attendances')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->where('attendances.user_id', $studentId)
            ->where('attendances.attended', true)
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->select(
                'field_sessions.name',
                'field_sessions.start_datetime as date',
                'field_sessions.location_name as location',
                DB::raw('(SELECT SUM(hours) FROM attendance_activities WHERE attendance_id = attendances.id AND deleted_at IS NULL) as hours')
            )
            ->orderByDesc('field_sessions.start_datetime')
            ->first();

        // Section average
        $sectionAverage = $this->getStudentSectionAverage($studentId, $yearId);

        // Evidence count
        $evidenceCount = DB::table('media')
            ->join('attendance_activities', 'media.model_id', '=', 'attendance_activities.id')
            ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
            ->where('attendances.user_id', $studentId)
            ->where('media.model_type', 'App\\Models\\AttendanceActivity')
            ->whereNull('attendance_activities.deleted_at')
            ->whereNull('attendances.deleted_at')
            ->count();

        return [
            'progress' => [
                'jornadaHours' => $progress['jornada_hours'],
                'externalHours' => $progress['external_hours'],
                'totalHours' => $progress['total_hours'],
                'quota' => $progress['quota'],
                'percentage' => $progress['percentage'],
                'status' => $progress['status'],
            ],
            'breakdownByYear' => $breakdownByYear,
            'breakdownByTerm' => $breakdownByTerm,
            'sessionHistory' => $sessionHistory,
            'closureProjection' => $closureProjection,
            'categoryParticipation' => $categoryParticipation,
            'mostRecentSession' => $mostRecentSession ? [
                'name' => $mostRecentSession->name,
                'date' => $mostRecentSession->date,
                'location' => $mostRecentSession->location,
                'hours' => round((float) ($mostRecentSession->hours ?? 0), 2),
            ] : null,
            'sectionAverage' => $sectionAverage,
            'evidenceCount' => $evidenceCount,
        ];
    }

    /**
     * Get representative dashboard with all linked students' progress.
     *
     * @return array{students: list<array{id: int, name: string, gradeName: string, sectionName: string, hours: float, quota: float, percentage: float, status: string, nextSession: array{name: string, date: string, location: string}|null}>}
     */
    public function getRepresentativeDashboard(int $representativeId, ?int $academicYearId = null): array
    {
        $yearId = $this->resolveYearId($academicYearId);

        // Get all students linked to this representative
        $studentRecords = DB::table('student_representatives')
            ->join('users', 'student_representatives.student_id', '=', 'users.id')
            ->where('student_representatives.representative_id', $representativeId)
            ->whereNull('student_representatives.deleted_at')
            ->whereNull('users.deleted_at')
            ->select('users.id as student_id', 'users.name as student_name')
            ->orderBy('users.name')
            ->get();

        $students = [];

        foreach ($studentRecords as $studentRecord) {
            $studentId = $studentRecord->student_id;

            // Progress
            $progress = $this->getStudentTotalHours($studentId, $yearId);

            // Current enrollment (grade and section) for active year
            $enrollment = DB::table('enrollments')
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->join('grades', 'sections.grade_id', '=', 'grades.id')
                ->where('enrollments.user_id', $studentId)
                ->whereNull('enrollments.deleted_at')
                ->whereNull('sections.deleted_at')
                ->whereNull('grades.deleted_at')
                ->when($yearId, fn ($q) => $q->where('enrollments.academic_year_id', $yearId))
                ->select('enrollments.section_id', 'sections.name as section_name', 'grades.name as grade_name')
                ->first();

            $gradeName = $enrollment->grade_name ?? '';
            $sectionName = $enrollment->section_name ?? '';
            $sectionId = $enrollment->section_id ?? null;

            // Next upcoming session for this student's section
            $nextSession = null;
            if ($sectionId !== null) {
                $teacherIds = DB::table('teacher_assignments')
                    ->where('section_id', $sectionId)
                    ->whereNull('deleted_at')
                    ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                    ->pluck('user_id');

                if ($teacherIds->isNotEmpty()) {
                    $nextSession = DB::table('field_sessions')
                        ->join('field_session_statuses', 'field_sessions.status_id', '=', 'field_session_statuses.id')
                        ->whereIn('field_sessions.user_id', $teacherIds)
                        ->where('field_session_statuses.name', 'planned')
                        ->where('field_sessions.start_datetime', '>', now())
                        ->whereNull('field_sessions.deleted_at')
                        ->select('field_sessions.name', 'field_sessions.start_datetime as date', 'field_sessions.location_name as location')
                        ->orderBy('field_sessions.start_datetime')
                        ->first();
                }
            }

            $students[] = [
                'id' => (int) $studentId,
                'name' => $studentRecord->student_name,
                'gradeName' => $gradeName,
                'sectionName' => $sectionName,
                'hours' => $progress['total_hours'],
                'quota' => $progress['quota'],
                'percentage' => $progress['percentage'],
                'status' => $progress['status'],
                'nextSession' => $nextSession ? [
                    'name' => $nextSession->name,
                    'date' => $nextSession->date,
                    'location' => $nextSession->location,
                ] : null,
            ];
        }

        return [
            'students' => $students,
        ];
    }

    /**
     * Calculate closure projection for a student.
     *
     * @return array{projected_date: string|null, days_remaining: int|null, is_on_track: bool}
     */
    protected function calculateClosureProjection(int $studentId, float $quota, float $currentHours): array
    {
        if ($currentHours >= $quota) {
            return [
                'projectedDate' => null,
                'daysRemaining' => 0,
                'isOnTrack' => true,
            ];
        }

        // Calculate average hours per week based on attendance history
        $firstAttendance = DB::table('attendances')
            ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
            ->where('attendances.user_id', $studentId)
            ->where('attendances.attended', true)
            ->whereNull('attendances.deleted_at')
            ->whereNull('field_sessions.deleted_at')
            ->orderBy('field_sessions.start_datetime')
            ->value('field_sessions.start_datetime');

        if ($firstAttendance === null) {
            return [
                'projectedDate' => null,
                'daysRemaining' => null,
                'isOnTrack' => false,
            ];
        }

        $weeksActive = max(1, now()->diffInWeeks($firstAttendance));
        $hoursPerWeek = $currentHours / $weeksActive;

        if ($hoursPerWeek <= 0) {
            return [
                'projectedDate' => null,
                'daysRemaining' => null,
                'isOnTrack' => false,
            ];
        }

        $remainingHours = $quota - $currentHours;
        $weeksRemaining = ceil($remainingHours / $hoursPerWeek);
        $daysRemaining = $weeksRemaining * 7;
        $projectedDate = now()->addDays($daysRemaining)->format('Y-m-d');

        return [
            'projectedDate' => $projectedDate,
            'daysRemaining' => $daysRemaining,
            'isOnTrack' => $hoursPerWeek >= ($quota / 40), // Assuming 40 weeks in a school year
        ];
    }

    /**
     * Get the average hours for the student's section.
     */
    protected function getStudentSectionAverage(int $studentId, ?int $academicYearId): float
    {
        $sectionId = DB::table('enrollments')
            ->where('user_id', $studentId)
            ->whereNull('deleted_at')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->value('section_id');

        if ($sectionId === null) {
            return 0;
        }

        $students = $this->getSectionProgress($sectionId, $academicYearId);

        if (empty($students)) {
            return 0;
        }

        $totalHours = array_sum(array_column($students, 'total_hours'));

        return round($totalHours / count($students), 2);
    }

    /**
     * Calculate total jornada hours for a student in a given year.
     *
     * Sums all attendance_activity.hours where attendance.attended = true.
     */
    protected function calculateJornadaHours(int $userId, ?int $academicYearId = null): float
    {
        $query = DB::table('attendance_activities')
            ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
            ->where('attendances.user_id', $userId)
            ->where('attendances.attended', true)
            ->whereNull('attendance_activities.deleted_at')
            ->whereNull('attendances.deleted_at');

        if ($academicYearId !== null) {
            $query->where('attendances.academic_year_id', $academicYearId);
        }

        return (float) ($query->sum('attendance_activities.hours') ?? 0);
    }

    /**
     * Calculate total external hours for a student (all-time, not year-specific).
     */
    protected function calculateExternalHours(int $userId): float
    {
        return (float) ExternalHour::where('user_id', $userId)->sum('hours');
    }

    /**
     * Get the quota for a given academic year.
     */
    protected function getQuota(?int $academicYearId): float
    {
        if ($academicYearId === null) {
            return 0;
        }

        $year = AcademicYear::find($academicYearId);

        return (float) ($year?->required_hours ?? 0);
    }

    /**
     * Resolve the academic year ID to use.
     * Returns the provided ID, or falls back to the active year.
     */
    protected function resolveYearId(?int $academicYearId): ?int
    {
        if ($academicYearId !== null) {
            return $academicYearId;
        }

        $active = AcademicYear::active()->first();

        return $active?->id;
    }
}
