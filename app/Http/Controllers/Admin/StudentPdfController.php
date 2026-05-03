<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ExternalHour;
use App\Models\Institution;
use App\Models\User;
use App\Services\HourAccumulatorService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

class StudentPdfController extends Controller
{
    public function __invoke(User $user, HourAccumulatorService $hourAccumulator): Response
    {
        Gate::authorize('view', $user);

        abort_unless($user->hasRole('alumno'), 403, 'Solo se puede exportar la ficha de alumnos.');

        $institution = Institution::first();

        $user->load([
            'enrollments' => function ($query) {
                $query->with('academicYear', 'grade', 'section')
                    ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                    ->orderBy('created_at', 'desc');
            },
        ]);

        $currentEnrollment = $user->enrollments->first();

        // --- Estadísticas de horas ---
        $activeYear = AcademicYear::active()->first();
        $currentYearData = $hourAccumulator->getStudentTotalHours($user->id, $activeYear?->id);

        $allYears = AcademicYear::all();
        $totalHoursAllYears = 0.0;
        $totalQuotaAllYears = 0.0;

        foreach ($allYears as $year) {
            $yearData = $hourAccumulator->getStudentTotalHours($user->id, $year->id);
            $totalHoursAllYears += $yearData['total_hours'];
            $totalQuotaAllYears += (float) $year->required_hours;
        }

        // Horas externas: suman solo al acumulado general
        $totalExternalHours = (float) ExternalHour::where('user_id', $user->id)->sum('hours');
        $totalHoursAllYears += $totalExternalHours;

        $totalPercentage = $totalQuotaAllYears > 0
            ? ($totalHoursAllYears / $totalQuotaAllYears) * 100
            : 0.0;

        // Desglose por lapso del año actual
        $breakdownByTerm = [];
        if ($activeYear) {
            $terms = \DB::table('school_terms')
                ->where('academic_year_id', $activeYear->id)
                ->whereNull('deleted_at')
                ->select('id', 'term_type_name')
                ->orderBy('id')
                ->get();

            $quotaPerTerm = $terms->count() > 0
                ? $activeYear->required_hours / $terms->count()
                : 0;

            foreach ($terms as $term) {
                $hours = \DB::table('attendance_activities')
                    ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
                    ->join('field_sessions', 'attendances.field_session_id', '=', 'field_sessions.id')
                    ->where('attendances.user_id', $user->id)
                    ->where('field_sessions.school_term_id', $term->id)
                    ->where('attendances.attended', true)
                    ->whereNull('attendance_activities.deleted_at')
                    ->whereNull('attendances.deleted_at')
                    ->whereNull('field_sessions.deleted_at')
                    ->sum('attendance_activities.hours');

                $hoursFloat = round((float) $hours, 2);
                $percentage = $quotaPerTerm > 0
                    ? ($hoursFloat / $quotaPerTerm) * 100
                    : 0;

                $breakdownByTerm[] = [
                    'termName' => $term->term_type_name,
                    'totalHours' => $hoursFloat,
                    'quota' => round($quotaPerTerm, 2),
                    'percentage' => round($percentage, 2),
                ];
            }
        }

        $hourStats = [
            'current_year' => [
                'hours' => $currentYearData['total_hours'] ?? 0.0,
                'required' => (float) ($activeYear?->required_hours ?? 0),
                'percentage' => $currentYearData['percentage'] ?? 0.0,
                'year_name' => $activeYear?->name ?? 'N/A',
            ],
            'total' => [
                'hours' => round($totalHoursAllYears, 2),
                'required' => round($totalQuotaAllYears, 2),
                'percentage' => round($totalPercentage, 2),
            ],
            'breakdown_by_term' => $breakdownByTerm,
        ];

        // --- Historial de jornadas ordenado cronológicamente por fecha de jornada ---
        $hourHistory = Attendance::where('user_id', $user->id)
            ->with(['fieldSession' => function ($query) {
                $query->with(['status', 'academicYear']);
            }, 'attendanceActivities.activityCategory'])
            ->get()
            ->sortByDesc(fn ($a) => $a->fieldSession?->start_datetime ?? $a->created_at)
            ->values()
            ->map(function ($a) {
                $totalHours = $a->attended
                    ? $a->attendanceActivities->sum('hours')
                    : 0;

                return [
                    'id' => $a->id,
                    'attended' => $a->attended,
                    'notes' => $a->notes,
                    'created_at' => $a->created_at->format('d/m/Y'),
                    'total_hours' => (float) $totalHours,
                    'fieldSession' => $a->fieldSession ? [
                        'id' => $a->fieldSession->id,
                        'name' => $a->fieldSession->name,
                        'start_datetime' => $a->fieldSession->start_datetime?->format('d/m/Y'),
                        'status' => $a->fieldSession->status?->name,
                        'academic_year_name' => $a->fieldSession->academicYear?->name,
                    ] : null,
                    'activities' => $a->attendanceActivities->map(fn ($act) => [
                        'id' => $act->id,
                        'hours' => (float) $act->hours,
                        'activity_category' => $act->activityCategory?->name,
                        'notes' => $act->notes,
                    ])->values()->toArray(),
                ];
            })
            ->toArray();

        // --- Agrupar historial por año académico (del más reciente al más antiguo) ---
        $hourHistoryGrouped = collect($hourHistory)
            ->groupBy('fieldSession.academic_year_name')
            ->sortKeysDesc()
            ->toArray();

        // --- Horas externas ordenadas cronológicamente por período ---
        $externalHours = ExternalHour::where('user_id', $user->id)
            ->with('admin:id,name')
            ->orderBy('period')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($eh) => [
                'id' => $eh->id,
                'period' => $eh->period,
                'hours' => (float) $eh->hours,
                'institution_name' => $eh->institution_name,
                'description' => $eh->description,
                'admin_name' => $eh->admin?->name,
            ])
            ->toArray();

        $generatedAt = now()->format('d/m/Y H:i');

        $html = View::make('pdf.student-report', compact(
            'user',
            'institution',
            'currentEnrollment',
            'hourStats',
            'hourHistoryGrouped',
            'externalHours',
            'generatedAt',
        ))->render();

        $options = new Options;
        $options->setIsPhpEnabled(true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $filename = "ficha-{$user->cedula}-{$user->id}.pdf";

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
