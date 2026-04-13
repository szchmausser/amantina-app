<?php

use Illuminate\Support\Facades\DB;

$students = DB::table('enrollments')
    ->join('users', 'enrollments.user_id', '=', 'users.id')
    ->whereNull('enrollments.deleted_at')
    ->whereNull('users.deleted_at')
    ->select('users.id', 'users.name', 'users.cedula')
    ->get();

echo "=== ESTUDIANTES CON HORAS ACUMULADAS ===\n\n";

$results = [];

foreach ($students as $student) {
    $totalHours = DB::table('attendance_activities')
        ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
        ->where('attendances.user_id', $student->id)
        ->where('attendances.attended', true)
        ->whereNull('attendance_activities.deleted_at')
        ->whereNull('attendances.deleted_at')
        ->sum('attendance_activities.hours');

    $attendanceCount = DB::table('attendances')
        ->where('user_id', $student->id)
        ->where('attended', true)
        ->whereNull('deleted_at')
        ->count();

    if ($totalHours > 0 || $attendanceCount > 0) {
        $results[] = [
            'id' => $student->id,
            'name' => $student->name,
            'cedula' => $student->cedula,
            'attendance_count' => $attendanceCount,
            'total_hours' => round($totalHours, 2),
        ];
        echo $student->id.' | '.$student->name.' | '.$student->cedula.' | Asistencias: '.$attendanceCount.' | Horas: '.round($totalHours, 2)."\n";
    }
}

echo "\n=== RESUMEN ===\n";
$totalAll = DB::table('attendance_activities')
    ->join('attendances', 'attendance_activities.attendance_id', '=', 'attendances.id')
    ->where('attendances.attended', true)
    ->whereNull('attendance_activities.deleted_at')
    ->whereNull('attendances.deleted_at')
    ->sum('attendance_activities.hours');
echo 'Total horas acumuladas: '.round($totalAll, 2)."\n";
echo 'Total estudiantes con horas: '.count($results)."\n";

return $results;
