<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionesController extends Controller
{
    /**
     * Devuelve las notificaciones no leídas del usuario autenticado.
     * Usado por el polling del front-end.
     */
    public function nuevas(Request $request)
    {
        $codigoDoc = auth()->user()->PROFILE;

        $notifs = DB::table('notificaciones')
            ->where('codigo_doc', $codigoDoc)
            ->where('leida', false)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'tipo', 'titulo', 'mensaje', 'created_at']);

        return response()->json([
            'total'         => $notifs->count(),
            'notificaciones'=> $notifs,
        ]);
    }

    /**
     * Marca una notificación como leída.
     */
    public function leer(int $id)
    {
        $codigoDoc = auth()->user()->PROFILE;

        DB::table('notificaciones')
            ->where('id', $id)
            ->where('codigo_doc', $codigoDoc)
            ->update(['leida' => true, 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function leerTodas()
    {
        $codigoDoc = auth()->user()->PROFILE;

        DB::table('notificaciones')
            ->where('codigo_doc', $codigoDoc)
            ->where('leida', false)
            ->update(['leida' => true, 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
