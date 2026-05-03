<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SeedCompleteTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed-test-data {--fresh : Drop all tables and migrate fresh before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with complete test data (students, teachers, sessions, activities)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Verificar si las tablas existen
        try {
            DB::table('users')->count();
        } catch (\Exception $e) {
            $this->error('❌ Las tablas de la base de datos no existen.');
            $this->info('💡 Ejecuta primero: php artisan migrate');
            $this->newLine();
            
            if ($this->confirm('¿Deseas ejecutar las migraciones ahora?', true)) {
                $this->info('🔄 Ejecutando migraciones...');
                Artisan::call('migrate', [], $this->getOutput());
                $this->newLine();
            } else {
                return self::FAILURE;
            }
        }
        
        if ($this->option('fresh')) {
            $this->warn('⚠️  Esta acción eliminará TODOS los datos de la base de datos.');
            
            if (! $this->confirm('¿Estás seguro de que deseas continuar?', false)) {
                $this->info('Operación cancelada.');
                return self::SUCCESS;
            }

            $this->info('🔄 Ejecutando migrate:fresh...');
            Artisan::call('migrate:fresh', [], $this->getOutput());
            $this->newLine();
        }

        $this->info('🌱 Iniciando semilla de datos de prueba...');
        $this->newLine();

        try {
            Artisan::call('db:seed', [
                '--class' => 'CompleteTestDataSeeder',
            ], $this->getOutput());

            $this->newLine();
            $this->info('✅ ¡Datos de prueba generados exitosamente!');
            $this->newLine();

            // Mostrar estadísticas
            $this->showStatistics();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error al generar datos de prueba:');
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function showStatistics(): void
    {
        $this->info('📊 Estadísticas:');
        
        $studentCount = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'alumno')
            ->count();
            
        $teacherCount = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'profesor')
            ->count();
        
        $this->table(
            ['Entidad', 'Cantidad'],
            [
                ['Usuarios', DB::table('users')->count()],
                ['Estudiantes', $studentCount],
                ['Profesores', $teacherCount],
                ['Secciones', DB::table('sections')->count()],
                ['Inscripciones', DB::table('enrollments')->count()],
                ['Jornadas', DB::table('field_sessions')->count()],
                ['Asistencias', DB::table('attendances')->count()],
                ['Actividades', DB::table('attendance_activities')->count()],
            ]
        );
    }
}
