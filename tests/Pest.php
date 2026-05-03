<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Browsable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| ⚠️ PROTOCOLO OBLIGATORIO ANTES DE EJECUTAR TESTS:
|
| SIEMPRE ejecutar estos comandos ANTES de correr cualquier test:
|
|   php artisan config:clear
|   php artisan cache:clear
|
| O en una sola línea:
|
|   php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/...
|
| Esto asegura que Laravel use la base de datos de testing (amantina_app_testing)
| y NO la de desarrollo (amantina_app), evitando pérdida de datos.
|
*/

// Feature tests (unit/integration) - usan RefreshDatabase
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Browser tests (E2E) - usan DatabaseTruncation y Browsable
//
// ⚠️ PROTOCOLO OBLIGATORIO ANTES DE EJECUTAR TESTS:
//
// SIEMPRE ejecutar estos comandos ANTES de correr cualquier test:
//
//   php artisan config:clear
//   php artisan cache:clear
//
// O en una sola línea:
//
//   php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/...
//
// Esto asegura que Laravel use la base de datos de testing (amantina_app_testing)
// y NO la de desarrollo (amantina_app), evitando pérdida de datos.
//
// IMPORTANTE: Browser tests usan DatabaseTruncation (no RefreshDatabase) para
// asegurar que la base de datos se limpia completamente entre tests, evitando
// problemas de datos duplicados o residuales.
//
pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTruncation::class)
    ->use(Browsable::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Browser Configuration
|--------------------------------------------------------------------------
|
| Configuración para browser tests con Playwright.
| - headed(): Muestra el navegador (útil para desarrollo y debugging)
| - timeout(): Tiempo máximo de espera para elementos (15 segundos)
| - SIN withHost(): Pest usa su servidor interno que respeta .env.testing
|
| MODO HEADED (VISUAL):
| - Permite ver la ejecución de los tests en tiempo real
| - Útil para debugging y verificar que los tests interactúan correctamente con el frontend
| - Recomendado durante desarrollo de nuevos tests
|
| Para ejecutar en modo headless (sin ventana), comentar la línea ->headed()
|
| ⚠️ PROTOCOLO OBLIGATORIO DE EJECUCIÓN:
|
| SIEMPRE ejecutar en este orden:
|
|   php artisan config:clear
|   php artisan cache:clear
|   php artisan test --env=testing --compact tests/Browser/...
|
| O en una sola línea:
|
|   php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/Browser/...
|
| ¿Por qué es obligatorio?
| - Laravel Herd cachea la configuración con .env (desarrollo)
| - Sin limpiar cache, los tests usan amantina_app (desarrollo) en lugar de amantina_app_testing
| - Esto causa PÉRDIDA DE DATOS en la base de datos de desarrollo
|
*/

pest()->browser()
    //->headed()                          // Modo visual - ver el navegador
    ->timeout(15000);                   // 15 segundos de timeout
    // NO usar ->withHost() para que Pest use su servidor interno con .env.testing

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
