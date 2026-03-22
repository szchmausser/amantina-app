<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CreateTestingDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create-testing
                            {--force : Crear incluso si ya existe (recrear)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea la base de datos amantina_app_testing para ejecutar tests sin afectar los datos de desarrollo';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dbName = 'amantina_app_testing';

        if (config('database.default') !== 'pgsql') {
            $this->error('Este comando requiere PostgreSQL como conexión por defecto en .env');

            return self::FAILURE;
        }

        try {
            $config = config('database.connections.pgsql');
            $tempConfig = array_merge($config, ['database' => 'postgres']);

            Config::set('database.connections.temp_pgsql', array_merge(
                ['driver' => 'pgsql'],
                $tempConfig
            ));

            DB::connection('temp_pgsql')->getPdo();

            $exists = DB::connection('temp_pgsql')
                ->select('SELECT 1 FROM pg_database WHERE datname = ?', [$dbName]);

            if (! empty($exists) && ! $this->option('force')) {
                $this->info("La base de datos «{$dbName}» ya existe.");

                return self::SUCCESS;
            }

            if (! empty($exists) && $this->option('force')) {
                DB::connection('temp_pgsql')->statement("DROP DATABASE IF EXISTS {$dbName}");
            }

            DB::connection('temp_pgsql')->statement("CREATE DATABASE {$dbName}");

            $this->info("Base de datos «{$dbName}» creada correctamente.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error al crear la base de datos: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
