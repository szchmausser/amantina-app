<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Validation\ValidationException;

class SetActiveRoleContext
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $request = request();
        $user = $event->user;
        $context = $request->input('context');

        // Si se envió un contexto, validamos agresivamente.
        if ($context) {
            if ($user->hasRole($context)) {
                session(['active_role' => $context]);

                return;
            }

            // Si el contexto solicitado no es válido para este usuario:
            // Deslogueamos y lanzamos error de validación.
            auth()->logout();
            throw ValidationException::withMessages([
                'context' => ['No tienes permiso para acceder con el rol seleccionado.'],
            ]);
        }

        // Si no hay contexto (automático), aplicamos la jerarquía de prioridad.
        $hierarchy = ['admin', 'profesor', 'alumno', 'representante'];
        $userRoles = $user->getRoleNames()->toArray();

        foreach ($hierarchy as $role) {
            if (in_array($role, $userRoles)) {
                session(['active_role' => $role]);

                return;
            }
        }
    }
}
