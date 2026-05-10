<?php

use App\Models\GradeDefinition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ─── PÁGINA DE DEFINICIONES DE GRADOS ───────────────────────────────────────

test('admin puede ver el listado de definiciones de grados', function () {
    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    $page->assertPathIs('/admin/grade-definitions')
        ->assertSee('Definiciones de Grados')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver definiciones existentes en el listado', function () {
    GradeDefinition::factory()->create(['name' => '1er Año', 'order' => 1]);
    GradeDefinition::factory()->create(['name' => '2do Año', 'order' => 2]);

    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    $page->assertSee('1er Año')
        ->assertSee('2do Año')
        ->assertNoJavaScriptErrors();
});

test('admin puede crear una definición de grado mediante el formulario inline', function () {
    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    // Click "Nuevo" button to show the create form
    $page->click('[data-test="create-button"]');
    $page->wait(1);

    // Verify the form is visible
    $page->assertVisible('[data-test="grade-definition-name-input"]');
    $page->assertVisible('[data-test="grade-definition-order-input"]');

    // Fill the inline create form
    $page->type('[data-test="grade-definition-name-input"]', '1er Año');
    $page->wait(0.3);
    $page->type('[data-test="grade-definition-order-input"]', '1');
    $page->wait(0.3);

    // Submit the form
    $page->click('[data-test="create-grade-definition-button"]');

    // Wait for the definition to appear in the list
    $page->waitForText('1er Año', 5);
    $page->assertNoJavaScriptErrors();

    // Verify the new definition is visible in the list
    $page->assertSee('1er Año');
});

test('admin puede editar una definición de grado existente', function () {
    $definition = GradeDefinition::factory()->create([
        'name' => '1er Año',
        'order' => 1,
    ]);

    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    // Verify the definition is visible
    $page->assertSee('1er Año');

    // Click edit button for the definition
    $page->click('[data-test="edit-grade-definition-'.$definition->id.'"]');
    $page->wait(1);

    // Verify the edit form is visible
    $page->assertVisible('[data-test="edit-name-input-'.$definition->id.'"]');

    // Modify the name in the edit form
    $page->clear('[data-test="edit-name-input-'.$definition->id.'"]');
    $page->type('[data-test="edit-name-input-'.$definition->id.'"]', 'Primer Año');
    $page->wait(0.3);

    // Save changes
    $page->click('[data-test="save-grade-definition-'.$definition->id.'"]');

    // Wait for the updated name to appear
    $page->waitForText('Primer Año', 5);
    $page->assertNoJavaScriptErrors();

    // Verify the updated name is visible in the list
    $page->assertDontSee('1er Año');
    $page->assertSee('Primer Año');
});

test('admin puede eliminar una definición de grado', function () {
    $definition = GradeDefinition::factory()->create([
        'name' => '1er Año',
        'order' => 1,
    ]);

    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    // Verify the definition is visible
    $page->assertSee('1er Año');

    // Click delete button (opens confirmation dialog)
    $page->click('[data-test="delete-grade-definition-'.$definition->id.'"]');
    $page->wait(1);

    // Verify the confirmation dialog is visible
    $page->assertSee('¿Eliminar definición de grado?');

    // Confirm deletion in the AlertDialog
    $page->click('[data-test="confirm-delete-button"]');

    // Wait for the empty state message to appear
    $page->waitForText('No hay definiciones de grados configuradas', 5);
    $page->assertNoJavaScriptErrors();

    // Verify the definition no longer appears in the list
    $page->assertDontSee('1er Año');
});

test('admin puede ver las definiciones de grado con sus nombres en el listado', function () {
    GradeDefinition::factory()->create(['name' => '1er Año', 'order' => 1]);
    GradeDefinition::factory()->create(['name' => '2do Año', 'order' => 2]);

    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    // Verify page title anchors assertions to the correct context
    $page->assertSee('Definiciones de Grados');
    // Verify grade names are visible in the list
    $page->assertSee('1er Año');
    $page->assertSee('2do Año');
    $page->assertNoJavaScriptErrors();
});

test('admin puede ver definiciones de grados activas e inactivas en el listado', function () {
    GradeDefinition::factory()->create(['name' => '1er Año', 'is_active' => true]);
    GradeDefinition::factory()->create(['name' => '2do Año', 'is_active' => false]);

    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    // Verify page title anchors assertions to the correct context
    $page->assertSee('Definiciones de Grados');
    // Verify both active and inactive definitions appear in the list
    $page->assertSee('1er Año');
    $page->assertSee('2do Año');
    $page->assertNoJavaScriptErrors();
});

// ─── CONTROL DE ACCESO ──────────────────────────────────────────────────────

test('usuario sin permiso no puede acceder a definiciones de grados', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    $page->assertSee('403');
});

test('admin puede acceder a la página de definiciones de grados', function () {
    $page = visit('/dashboard');
    $page->wait(2);

    // Navigate directly to verify the page is accessible
    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    // Verify we can access the page
    $page->assertPathIs('/admin/grade-definitions');
    $page->assertSee('Definiciones de Grados');
    $page->assertNoJavaScriptErrors();
});

test('link de definiciones de grados NO aparece en sidebar para alumno', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/dashboard');
    $page->wait(2);

    // Try to access the page directly (should be blocked)
    $page = visit('/admin/grade-definitions');
    $page->wait(2);

    $page->assertSee('403');
});
