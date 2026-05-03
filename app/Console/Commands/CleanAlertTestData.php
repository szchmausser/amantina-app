<?php

namespace App\Console\Commands;

use App\Models\FieldSession;
use Illuminate\Console\Command;

class CleanAlertTestData extends Command
{
    protected $signature = 'test:clean-alerts';

    protected $description = 'Clean test data generated for dashboard alerts';

    public function handle(): int
    {
        $this->info('Limpiando datos de prueba de alertas...');

        // Eliminar jornadas de prueba (esto eliminará en cascada las asistencias y actividades)
        $deleted = FieldSession::where('name', 'LIKE', '[TEST]%')->delete();

        $this->info("✅ {$deleted} jornada(s) de prueba eliminadas");
        $this->info('Las asistencias y actividades relacionadas también fueron eliminadas.');

        return 0;
    }
}
