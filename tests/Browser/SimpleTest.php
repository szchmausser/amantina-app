<?php

test('puede visitar la página de bienvenida', function () {
    $this->visit('/')
        ->wait(2)
        ->assertSee('Amantina')
        ->assertNoJavaScriptErrors()
        // Mantener abierto para inspección
        ->wait(999999);
})->skip('Test de diagnóstico - ejecutar manualmente para verificar configuración');
