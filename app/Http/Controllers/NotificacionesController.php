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
        $user      = auth()->user();
        $codigoDoc = $user->PROFILE;

        // ── Recordatorio de copia de seguridad a las 4 pm ────────────────────
        $perfilesBackup = ['SuperAd', 'Admin', 'Contab'];
        $necesitaBackup = in_array($codigoDoc, $perfilesBackup)
            || str_starts_with($codigoDoc, 'Sec');

        if ($necesitaBackup && now()->hour >= 16) {
            $backupHecho = DB::table('copias_seguridad')
                ->where('usuario', $user->USER)
                ->whereDate('fecha', today())
                ->exists();

            if (!$backupHecho) {
                $yaNotificado = DB::table('notificaciones')
                    ->where('codigo_doc', $codigoDoc)
                    ->where('tipo', 'backup')
                    ->whereDate('created_at', today())
                    ->exists();

                if (!$yaNotificado) {
                    DB::table('notificaciones')->insert([
                        'codigo_doc' => $codigoDoc,
                        'tipo'       => 'backup',
                        'titulo'     => 'Copia de seguridad pendiente',
                        'mensaje'    => 'Son las 4 p.m. Recuerda descargar la copia de seguridad antes de terminar tu jornada.',
                        'leida'      => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        // ────────────────────────────────────────────────────────────────────

        $notifs = DB::table('notificaciones')
            ->where('codigo_doc', $codigoDoc)
            ->where('leida', false)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'tipo', 'titulo', 'mensaje', 'created_at']);

        return response()->json([
            'total'          => $notifs->count(),
            'notificaciones' => $notifs,
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
