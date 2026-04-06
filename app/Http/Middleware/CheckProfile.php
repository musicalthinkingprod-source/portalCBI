<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfile
{
    /**
     * Verifica que el perfil del usuario autenticado esté entre los permitidos.
     *
     * Los perfiles se pasan como parámetros separados por comas.
     * Se puede usar el comodín * al final para prefijos: 'Sec*', 'DOC*'.
     *
     * Ejemplo de uso en rutas:
     *   ->middleware('profile:SuperAd,Admin')
     *   ->middleware('profile:SuperAd,Admin,Sec*,DOC*')
     */
    public function handle(Request $request, Closure $next, string ...$perfiles): mixed
    {
        $profile = auth()->user()?->PROFILE;

        foreach ($perfiles as $permitido) {
            if (str_ends_with($permitido, '*')) {
                $prefix = substr($permitido, 0, -1);
                if (str_starts_with($profile, $prefix)) {
                    return $next($request);
                }
            } elseif ($profile === $permitido) {
                return $next($request);
            }
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
