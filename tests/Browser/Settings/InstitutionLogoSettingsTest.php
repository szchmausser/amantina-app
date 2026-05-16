<?php

namespace Tests\Browser\Settings;

use App\Models\Institution;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

/**
 * Helper: create a test PNG image file and return the absolute path.
 */
function createTestImage(): string
{
    $dir = storage_path('app/test');

    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $dir.DIRECTORY_SEPARATOR.'logo-test-'.uniqid().'.png';
    imagepng(imagecreatetruecolor(200, 200), $path);

    return realpath($path) ?: $path;
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->tempFiles = [];
});

afterEach(function () {
    foreach ($this->tempFiles as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
});

test('la pagina de configuracion muestra la seccion de subida de logo', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);

    $page = visit('/settings/institution');

    $page->assertSee('Datos Institucionales')
        ->assertSee('Logo de la Institución')
        ->assertSee('JPG, PNG, GIF o WebP. Máximo 10MB.')
        ->assertNoJavaScriptErrors();
});

test('usuario administrador puede ver el logo en la pagina de configuracion cuando existe', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $institution = Institution::create(['name' => 'Escuela Test']);

    $path = createTestImage();
    $this->tempFiles[] = $path;
    $institution->addMedia($path)->toMediaCollection('logo');

    $this->actingAs($user);

    $page = visit('/settings/institution');

    $page->assertVisible('[data-testid="logo-preview"]')
        ->assertVisible('[data-testid="logo-remove-btn"]')
        ->assertNoJavaScriptErrors();
});

test('usuario administrador puede eliminar el logo desde la interfaz', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $institution = Institution::create(['name' => 'Escuela Test']);

    $path = createTestImage();
    $this->tempFiles[] = $path;
    $institution->addMedia($path)->toMediaCollection('logo');

    $this->actingAs($user);

    $page = visit('/settings/institution');
    $page->assertVisible('[data-testid="logo-remove-btn"]');
    $page->click('[data-testid="logo-remove-btn"]');
    $page->waitForText('Logo eliminado correctamente');
    $page->assertDontSee('Eliminar logo');
    $page->assertNoJavaScriptErrors();
});

test('el logo institucional se muestra en el AppLogo del sidebar luego de subirlo', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $institution = Institution::create(['name' => 'Escuela Test']);

    $path = createTestImage();
    $this->tempFiles[] = $path;
    $institution->addMedia($path)->toMediaCollection('logo');

    $this->actingAs($user);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertVisible('[data-testid="app-logo-image"]')
        ->assertNoJavaScriptErrors();
});
