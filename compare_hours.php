<?php

use App\Services\HourAccumulatorService;
use Illuminate\Support\Facades\DB;

$service = new HourAccumulatorService;

$students = DB::table('enrollments')
    ->join('users', 'enrollments.user_id', '=', 'users.id')
    ->whereNull('enrollments.deleted_at')
    ->whereNull('users.deleted_at')
    ->select('users.id', 'users.name', 'users.cedula')
    ->get();

echo "=== COMPARACION: BASE DE DATOS vs SERVICE ===\n\n";

foreach ($students as $student) {
    // Calculate from DB directly
    $dbHours = DB::table('attendance_activities')
        ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
        ->where('attendances.user_id', $student->id)
        ->where('attendances.attended', true)
        ->whereNull('attendance_activities.deleted_at')
        ->whereNull('attendances.deleted_at')
        ->sum('attendance_activities.hours');

    // Calculate using service
    $serviceResult = $service->getStudentTotalHours($student->id, null);

    $diff = abs($dbHours - $serviceResult['jornada_hours']);
    $match = $diff < 0.01 ? 'OK' : 'MISMATCH!';

    echo $student->id.' | '.$student->name."\n";
    echo '  DB directa: '.round($dbHours, 2).'h'."\n";
    echo '  Service:    '.$serviceResult['jornada_hours'].'h'."\n";
    echo '  Estado:     '.$match."\n";
    echo "  ---\n";
}

echo "\n=== USANDO getSectionProgress (para estudiantes en secciones) ===\n\n";

// Get sections
$sections = DB::table('sections')
    ->join('grades', 'sections.grade_id', '=', 'grades.id')
    ->whereNull('sections.deleted_at')
    ->whereNull('grades.deleted_at')
    ->select('sections.id', 'sections.name as section_name', 'grades.name as grade_name')
    ->get();

foreach ($sections as $section) {
    $sectionStudents = $service->getSectionProgress($section->id, null);
    if (! empty($sectionStudents)) {
        echo 'Seccion: '.$section->section_name.' ('.$section->grade_name.')'."\n";
        foreach ($sectionStudents as $studentId => $data) {
            echo '  Student ID '.$studentId.': '.$data['jornada_hours'].'h | Status: '.$data['status']."\n";
        }
        echo "\n";
    }
}
