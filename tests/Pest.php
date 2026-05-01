<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Browsable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

// Feature tests (unit/integration) - usan RefreshDatabase
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Browser tests (E2E) - usan RefreshDatabase y Browsable
//
// ⚠️ IMPORTANTE: Para ejecutar estos tests correctamente, usa:
//    .\run-browser-tests.bat tests/Browser/Admin/UserManagementTest.php
//
// El script usa --env=testing para cargar .env.testing automáticamente.
// Lee tests/Browser/README.md para más detalles.
//
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
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
| - withHost(): Dominio de Laravel Herd (amantina-app.test)
|
| MODO HEADED (VISUAL):
| - Permite ver la ejecución de los tests en tiempo real
| - Útil para debugging y verificar que los tests interactúan correctamente con el frontend
| - Recomendado durante desarrollo de nuevos tests
|
| Para ejecutar en modo headless (sin ventana), comentar la línea ->headed()
|
| ✅ EJECUCIÓN CORRECTA:
| Usa el script: .\run-browser-tests.bat tests/Browser/Admin/UserManagementTest.php
| El script usa --env=testing para cargar .env.testing automáticamente.
|
*/

pest()->browser()
    // ->headed()                          // Modo visual - ver el navegador (comentado temporalmente)
    ->timeout(15000)                    // 15 segundos de timeout
    ->withHost('amantina-app.test');    // Dominio de Herd

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
