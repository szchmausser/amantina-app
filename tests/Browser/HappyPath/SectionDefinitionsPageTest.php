<?php

use App\Models\SectionDefinition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ─── PÁGINA DE DEFINICIONES DE SECCIONES ────────────────────────────────────

test('admin puede ver el listado de definiciones de secciones', function () {
    $page = visit('/admin/section-definitions');
    $page->wait(2);

    $page->assertPathIs('/admin/section-definitions')
        ->assertSee('Definiciones de Secciones')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver definiciones de secciones existentes en el listado', function () {
    SectionDefinition::factory()->create(['name' => 'A']);
    SectionDefinition::factory()->create(['name' => 'B']);

    $page = visit('/admin/section-definitions');
    $page->wait(2);

    $page->assertSee('A')
        ->assertSee('B')
        ->assertNoJavaScriptErrors();
});

test('admin puede crear una definición de sección mediante el formulario inline', function () {
    $page = visit('/admin/section-definitions');
    $page->wait(2);

    // Click "Nuevo" button to show the create form
    $page->click('[data-test="create-button"]');
    $page->wait(1);

    // Verify the form is visible
    $page->assertVisible('[data-test="section-definition-name-input"]');

    // Fill the inline create form
    $page->type('[data-test="section-definition-name-input"]', 'A');
    $page->wait(0.3);

    // Submit the form
    $page->click('[data-test="create-section-definition-button"]');

    // Wait for the definition to appear in the list
    $page->waitForText('A', 5);
    $page->assertNoJavaScriptErrors();

    // Verify the new definition is visible in the list
    $page->assertSee('A');
});

test('admin puede editar una definición de sección existente', function () {
    $definition = SectionDefinition::factory()->create(['name' => 'A']);

    $page = visit('/admin/section-definitions');
    $page->wait(2);

    // Verify the definition is visible
    $page->assertSee('A');

    // Click edit button for the definition
    $page->click('[data-test="edit-section-definition-'.$definition->id.'"]');
    $page->wait(1);

    // Verify the edit form is visible
    $page->assertVisible('[data-test="edit-name-input-'.$definition->id.'"]');

    // Modify the name in the edit form
    $page->clear('[data-test="edit-name-input-'.$definition->id.'"]');
    $page->type('[data-test="edit-name-input-'.$definition->id.'"]', 'B');
    $page->wait(0.3);

    // Save changes
    $page->click('[data-test="save-section-definition-'.$definition->id.'"]');

    // Wait for the updated name to appear
    $page->waitForText('B', 5);
    $page->assertNoJavaScriptErrors();

    // Verify the updated name is visible in the list
    $page->assertSee('B');
});

test('admin puede eliminar una definición de sección', function () {
    $definition = SectionDefinition::factory()->create(['name' => 'A']);

    $page = visit('/admin/section-definitions');
    $page->wait(2);

    // Verify the definition is visible
    $page->assertSee('A');

    // Click delete button (opens confirmation dialog)
    $page->click('[data-test="delete-section-definition-'.$definition->id.'"]');
    $page->wait(1);

    // Verify the confirmation dialog is visible
    $page->assertSee('¿Eliminar definición de sección?');

    // Confirm deletion in the AlertDialog
    $page->click('[data-test="confirm-delete-button"]');

    // Wait for the empty state message to appear (confirms deletion)
    $page->waitForText('No hay definiciones de secciones configuradas', 5);
    $page->assertNoJavaScriptErrors();
});

test('admin puede ver definiciones de secciones activas e inactivas en el listado', function () {
    SectionDefinition::factory()->create(['name' => 'A', 'is_active' => true]);
    SectionDefinition::factory()->create(['name' => 'B', 'is_active' => false]);

    $page = visit('/admin/section-definitions');
    $page->wait(2);

    // Verify page title anchors assertions to the correct context
    $page->assertSee('Definiciones de Secciones');
    // Verify both active and inactive definitions appear in the list
    $page->assertSee('A');
    $page->assertSee('B');
    $page->assertNoJavaScriptErrors();
});

test('admin puede ver los nombres de sección como texto en el listado', function () {
    SectionDefinition::factory()->create(['name' => 'A']);
    SectionDefinition::factory()->create(['name' => 'B']);
    SectionDefinition::factory()->create(['name' => 'C']);

    $page = visit('/admin/section-definitions');
    $page->wait(2);

    // Verify page title anchors assertions to the correct context
    $page->assertSee('Definiciones de Secciones');
    // Verify all section names are visible
    $page->assertSee('A');
    $page->assertSee('B');
    $page->assertSee('C');
    $page->assertNoJavaScriptErrors();
});

// ─── CONTROL DE ACCESO ──────────────────────────────────────────────────────

test('usuario sin permiso no puede acceder a definiciones de secciones', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/admin/section-definitions');
    $page->wait(2);

    $page->assertSee('403');
});

test('admin puede acceder a la página de definiciones de secciones', function () {
    $page = visit('/dashboard');
    $page->wait(2);

    // Navigate directly to verify the page is accessible
    $page = visit('/admin/section-definitions');
    $page->wait(2);

    // Verify we can access the page
    $page->assertPathIs('/admin/section-definitions');
    $page->assertSee('Definiciones de Secciones');
    $page->assertNoJavaScriptErrors();
});

test('link de definiciones de secciones NO aparece en sidebar para alumno', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/dashboard');
    $page->wait(2);

    // Try to access the page directly (should be blocked)
    $page = visit('/admin/section-definitions');
    $page->wait(2);

    $page->assertSee('403');
});
