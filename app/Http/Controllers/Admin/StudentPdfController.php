<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
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

        $totalPercentage = $totalQuotaAllYears > 0
            ? ($totalHoursAllYears / $totalQuotaAllYears) * 100
            : 0.0;

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
        ];

        // --- Historial completo de jornadas ---
        $hourHistory = Attendance::where('user_id', $user->id)
            ->with(['fieldSession' => function ($query) {
                $query->with(['status', 'academicYear']);
            }, 'attendanceActivities.activityCategory'])
            ->orderBy('created_at', 'asc')
            ->get()
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

        $generatedAt = now()->format('d/m/Y H:i');

        $html = View::make('pdf.student-report', compact(
            'user',
            'institution',
            'currentEnrollment',
            'hourStats',
            'hourHistory',
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
