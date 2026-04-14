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

        $miUltimaCopia = DB::table('copias_seguridad')
            ->where('usuario', auth()->user()->USER)
            ->orderByDesc('created_at')
            ->first();

        $copiaHoy = DB::table('copias_seguridad')
            ->whereDate('fecha', today())
            ->exists();

        $historial  = collect();
        $porUsuario = collect();

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
        @ini_set('memory_limit', '-1');
        @set_time_limit(0);

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
        $tmpPath  = storage_path('app') . DIRECTORY_SEPARATOR . 'portalcbi_' . uniqid() . '.sql';

        // Cabecera legible
        $header  = "-- ==========================================================\n";
        $header .= "-- Portal CBI — Copia de Seguridad\n";
        $header .= "-- Base de datos : {$dbName}\n";
        $header .= "-- Generada      : " . now()->format('d/m/Y H:i:s') . " (America/Bogota)\n";
        $header .= "-- Usuario       : " . auth()->user()->USER . " (" . auth()->user()->PROFILE . ")\n";
        $header .= "-- ==========================================================\n\n";

        file_put_contents($tmpPath, $header);

        $mysqldump = $this->encontrarMysqldump();

        if ($mysqldump && function_exists('proc_open')) {
            $ok = $this->dumpConMysqldump($mysqldump, $tmpPath, $dbName, $dbHost, $dbPort, $dbUser, $dbPass);
            if (!$ok) {
                // Si mysqldump fallo, reescribir el archivo con el método PHP
                file_put_contents($tmpPath, $header);
                $this->dumpConPHP($tmpPath);
            }
        } else {
            $this->dumpConPHP($tmpPath);
        }

        return response()->download($tmpPath, $filename, [
            'Content-Type'        => 'application/octet-stream',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ])->deleteFileAfterSend(true);
    }

    // ── Busca mysqldump en rutas comunes según el SO ─────────────────────────

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

        if (function_exists('shell_exec')) {
            $which = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump.exe 2>NUL' : 'which mysqldump 2>/dev/null';
            $found = trim((string) shell_exec($which));
            if ($found && is_executable($found)) return $found;
        }

        return null;
    }

    // ── Dump con mysqldump → escribe directo al archivo temporal ────────────

    private function dumpConMysqldump(string $mysqldump, string $tmpPath, string $dbName, string $dbHost, int|string $dbPort, string $dbUser, string $dbPass): bool
    {
        $cnfPath = tempnam(storage_path('app'), 'dmp') . '.cnf';
        file_put_contents($cnfPath, implode("\n", [
            '[client]',
            'user=' . $dbUser,
            'password=' . $dbPass,
            'host=' . $dbHost,
            'port=' . (int) $dbPort,
        ]));

        $stderr = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';

        $cmd = sprintf(
            '"%s" --defaults-extra-file="%s" --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 %s "%s"',
            $mysqldump,
            $cnfPath,
            $stderr,
            $dbName
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        $exito = false;

        if (is_resource($process)) {
            fclose($pipes[0]);

            $fh = fopen($tmpPath, 'ab');
            while (!feof($pipes[1])) {
                $chunk = fread($pipes[1], 65536);
                if ($chunk !== false && $chunk !== '') {
                    fwrite($fh, $chunk);
                    $exito = true;
                }
            }
            fclose($fh);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        @unlink($cnfPath);

        return $exito;
    }

    // ── Dump PHP puro (fallback) → escribe al archivo temporal ──────────────

    private function dumpConPHP(string $tmpPath): void
    {
        $pdo = DB::connection()->getPdo();
        $fh  = fopen($tmpPath, 'ab');

        fwrite($fh, "SET NAMES utf8mb4;\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fh, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tablesResult = DB::select('SHOW TABLES');

        foreach ($tablesResult as $tableRow) {
            $table = array_values((array) $tableRow)[0];

            $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql    = $createResult[0]->{'Create Table'};

            fwrite($fh, "-- ----------------------------------------------------------\n");
            fwrite($fh, "-- Tabla: `{$table}`\n");
            fwrite($fh, "-- ----------------------------------------------------------\n");
            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fh, $createSql . ";\n\n");

            $offset   = 0;
            $chunk    = 200;
            $hayDatos = false;

            while (true) {
                $rows = DB::table($table)->offset($offset)->limit($chunk)->get();

                if ($rows->isEmpty()) break;

                if (!$hayDatos) {
                    fwrite($fh, "INSERT INTO `{$table}` VALUES\n");
                    $hayDatos = true;
                } else {
                    fwrite($fh, ",\n");
                }

                $lines = [];
                foreach ($rows as $row) {
                    $cols = array_map(function ($v) use ($pdo) {
                        if ($v === null)                 return 'NULL';
                        if (is_int($v) || is_float($v)) return $v;
                        return $pdo->quote($v);
                    }, (array) $row);
                    $lines[] = '(' . implode(', ', $cols) . ')';
                }

                fwrite($fh, implode(",\n", $lines));

                $offset += $chunk;

                if ($rows->count() < $chunk) break;
            }

            if ($hayDatos) fwrite($fh, ";\n");

            fwrite($fh, "\n");
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fwrite($fh, "-- Fin de la copia de seguridad\n");
        fclose($fh);
    }
}
