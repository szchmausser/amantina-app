<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->session()->has('active_role')) {
            $this->initializeRoleContext($request);
        }

        return $next($request);
    }

    /**
     * Inicializa el rol activo en sesión basándose en la jerarquía de prioridad.
     */
    private function initializeRoleContext(Request $request): void
    {
        $user = $request->user();
        $roles = $user->getRoleNames()->toArray();

        // Jerarquía oficial según especificaciones
        $hierarchy = [
            'admin',
            'profesor',
            'alumno',
            'representante',
        ];

        foreach ($hierarchy as $role) {
            if (in_array($role, $roles)) {
                $request->session()->put('active_role', $role);

                return;
            }
        }
    }
}
