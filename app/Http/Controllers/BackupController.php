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

        $dbName  = config('database.connections.mysql.database');
        $dbHost  = config('database.connections.mysql.host');
        $dbPort  = config('database.connections.mysql.port', 3306);
        $dbUser  = config('database.connections.mysql.username');
        $dbPass  = config('database.connections.mysql.password');

        $filename = 'backup_' . $dbName . '_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $usuario  = auth()->user()->USER;
        $profile  = auth()->user()->PROFILE;
        $fecha    = now()->format('d/m/Y H:i:s');

        return response()->stream(function () use ($dbName, $dbHost, $dbPort, $dbUser, $dbPass, $usuario, $profile, $fecha) {

            @ini_set('memory_limit', '-1');
            @set_time_limit(0);

            // Cabecera legible
            echo "-- ==========================================================\n";
            echo "-- Portal CBI — Copia de Seguridad\n";
            echo "-- Base de datos : {$dbName}\n";
            echo "-- Generada      : {$fecha} (America/Bogota)\n";
            echo "-- Usuario       : {$usuario} ({$profile})\n";
            echo "-- ==========================================================\n\n";
            ob_flush(); flush();

            $mysqldump = $this->encontrarMysqldump();

            if ($mysqldump) {
                $this->dumpConMysqldump($mysqldump, $dbName, $dbHost, $dbPort, $dbUser, $dbPass);
            } else {
                $this->dumpConPHP($dbName);
            }

        }, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    // ── Busca el ejecutable mysqldump según el sistema operativo ────────────

    private function encontrarMysqldump(): ?string
    {
        $candidatos = PHP_OS_FAMILY === 'Windows'
            ? [
                'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe',
                'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            ]
            : [
                '/usr/bin/mysqldump',
                '/usr/local/bin/mysqldump',
                '/usr/local/mysql/bin/mysqldump',
            ];

        foreach ($candidatos as $path) {
            if (is_executable($path)) return $path;
        }

        // Último recurso: buscarlo en el PATH del sistema
        $which = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump.exe 2>NUL' : 'which mysqldump 2>/dev/null';
        $found = trim((string) shell_exec($which));
        return ($found && is_executable($found)) ? $found : null;
    }

    // ── Dump usando mysqldump (completo, rápido, sin límite de memoria) ─────

    private function dumpConMysqldump(string $mysqldump, string $dbName, string $dbHost, int|string $dbPort, string $dbUser, string $dbPass): void
    {
        // Credenciales en archivo temporal para no exponerlas en la línea de comando
        $cnfPath = tempnam(sys_get_temp_dir(), 'dmp') . '.cnf';
        file_put_contents($cnfPath, implode("\n", [
            '[client]',
            'user=' . $dbUser,
            'password=' . $dbPass,
            'host=' . $dbHost,
            'port=' . (int) $dbPort,
        ]));

        $cmd = sprintf(
            '"%s" --defaults-extra-file="%s" --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 2>/dev/null "%s"',
            $mysqldump,
            $cnfPath,
            $dbName
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);

            while (!feof($pipes[1])) {
                echo fread($pipes[1], 65536);
                ob_flush(); flush();
            }

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        @unlink($cnfPath);
    }

    // ── Dump en PHP puro (fallback) — sin el bug de comas ──────────────────

    private function dumpConPHP(string $dbName): void
    {
        $pdo = DB::connection()->getPdo();

        echo "SET NAMES utf8mb4;\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n";
        echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

        $tablesResult = DB::select('SHOW TABLES');

        foreach ($tablesResult as $tableRow) {
            $table = array_values((array) $tableRow)[0];

            $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql    = $createResult[0]->{'Create Table'};

            echo "-- ----------------------------------------------------------\n";
            echo "-- Tabla: `{$table}`\n";
            echo "-- ----------------------------------------------------------\n";
            echo "DROP TABLE IF EXISTS `{$table}`;\n";
            echo $createSql . ";\n\n";

            // Datos en lotes usando cursor (sin acumular en memoria)
            $offset = 0;
            $chunk  = 200;
            $hayDatos = false;

            while (true) {
                $rows = DB::table($table)->offset($offset)->limit($chunk)->get();

                if ($rows->isEmpty()) break;

                if (!$hayDatos) {
                    // Primer lote: abrir INSERT
                    echo "INSERT INTO `{$table}` VALUES\n";
                    $hayDatos = true;
                } else {
                    // Lotes siguientes: separador entre grupos
                    echo ",\n";
                }

                $lines = [];
                foreach ($rows as $row) {
                    $cols = array_map(function ($v) use ($pdo) {
                        if ($v === null)                    return 'NULL';
                        if (is_int($v) || is_float($v))    return $v;
                        return $pdo->quote($v);
                    }, (array) $row);
                    $lines[] = '(' . implode(', ', $cols) . ')';
                }

                echo implode(",\n", $lines);

                $offset += $chunk;

                ob_flush(); flush();

                if ($rows->count() < $chunk) break;
            }

            if ($hayDatos) {
                echo ";\n";
            }

            echo "\n";
            ob_flush(); flush();
        }

        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        echo "-- Fin de la copia de seguridad\n";
    }
}
