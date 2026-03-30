<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificadoPadre
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('padre_verificado')) {
            return redirect('/')->withErrors(['verificacion' => 'Debes verificar tu identidad para acceder.']);
        }

        return $next($request);
    }
}
