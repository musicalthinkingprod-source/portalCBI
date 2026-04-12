<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    /** Perfiles autorizados (además de SuperAd que tiene acceso total) */
    private const PERFILES_BACKUP = ['SuperAd', 'Admin', 'Contab'];

    public function index()
    {
        $profile   = auth()->user()->PROFILE;
        $isSuperAd = $profile === 'SuperAd';

        // Última copia del usuario actual
        $miUltimaCopia = DB::table('copias_seguridad')
            ->where('usuario', auth()->user()->USER)
            ->orderByDesc('created_at')
            ->first();

        // ¿Ya se hizo copia hoy (cualquier usuario del grupo)?
        $copiaHoy = DB::table('copias_seguridad')
            ->whereDate('fecha', today())
            ->exists();

        // Para SuperAd: historial completo + resumen por usuario
        $historial   = collect();
        $porUsuario  = collect();

        if ($isSuperAd) {
            $historial = DB::table('copias_seguridad')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            $porUsuario = DB::table('copias_seguridad')
                ->select('usuario', 'profile', DB::raw('MAX(created_at) as ultima'), DB::raw('COUNT(*) as total'))
                ->groupBy('usuario', 'profile')
                ->orderByDesc('ultima')
                ->get();
        }

        return view('backup.index', compact(
            'profile', 'isSuperAd', 'miUltimaCopia', 'copiaHoy', 'historial', 'porUsuario'
        ));
    }

    public function descargar(Request $request)
    {
        // Registrar la descarga ANTES de empezar el stream
        DB::table('copias_seguridad')->insert([
            'usuario'    => auth()->user()->USER,
            'profile'    => auth()->user()->PROFILE,
            'fecha'      => today()->toDateString(),
            'ip'         => $request->ip(),
            'created_at' => now(),
        ]);

        $dbName   = config('database.connections.mysql.database');
        $filename = 'backup_' . $dbName . '_' . now()->format('Y-m-d_H-i-s') . '.sql';

        return response()->stream(function () use ($dbName) {
            $pdo = DB::connection()->getPdo();

            echo "-- ==========================================================\n";
            echo "-- Portal CBI — Copia de Seguridad\n";
            echo "-- Base de datos : {$dbName}\n";
            echo "-- Generada      : " . now()->format('d/m/Y H:i:s') . " (America/Bogota)\n";
            echo "-- Usuario       : " . auth()->user()->USER . " (" . auth()->user()->PROFILE . ")\n";
            echo "-- ==========================================================\n\n";
            echo "SET NAMES utf8mb4;\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

            $tablesResult = DB::select('SHOW TABLES');

            foreach ($tablesResult as $tableRow) {
                $table = array_values((array) $tableRow)[0];

                // --- Estructura ---
                $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
                $createSql    = $createResult[0]->{'Create Table'};

                echo "-- ----------------------------------------------------------\n";
                echo "-- Tabla: `{$table}`\n";
                echo "-- ----------------------------------------------------------\n";
                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                echo $createSql . ";\n\n";

                // --- Datos en lotes de 500 ---
                $offset = 0;
                $chunk  = 500;
                $first  = true;

                while (true) {
                    $rows = DB::table($table)->offset($offset)->limit($chunk)->get();

                    if ($rows->isEmpty()) break;

                    if ($first) {
                        echo "INSERT INTO `{$table}` VALUES\n";
                        $first = false;
                    }

                    $valueLines = $rows->map(function ($row) use ($pdo) {
                        $cols = array_map(function ($v) use ($pdo) {
                            if ($v === null)         return 'NULL';
                            if (is_int($v) || is_float($v)) return $v;
                            return $pdo->quote($v);
                        }, (array) $row);

                        return '(' . implode(', ', $cols) . ')';
                    })->implode(",\n");

                    // Si no es el primer lote y se necesita coma, añadir al anterior bloque
                    echo $valueLines;

                    $offset += $chunk;

                    if ($rows->count() < $chunk) break;

                    echo ",\n"; // continúa INSERT en siguiente lote
                    ob_flush();
                    flush();
                }

                if (!$first) {
                    echo ";\n";
                }

                echo "\n";
                ob_flush();
                flush();
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            echo "-- Fin de la copia de seguridad\n";

        }, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
            'X-Accel-Buffering'   => 'no',
        ]);
    }
}
