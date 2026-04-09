<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VigilanciaController extends Controller
{
    // Vista para el docente: ve sus posiciones asignadas
    public function docente()
    {
        $profile = auth()->user()->PROFILE;
        $anio    = (int) date('Y');

        // Día del ciclo que corresponde a hoy según el calendario
        $hoy     = now()->toDateString();
        $diaHoy  = DB::table('calendario_vigilancias')
            ->where('fecha', $hoy)
            ->value('dia_ciclo');

        // Todas las asignaciones del docente para este año
        $filas = DB::table('vigilancias')
            ->where('CODIGO_DOC', $profile)
            ->where('ANIO', $anio)
            ->get();

        // Matriz [DIA_CICLO][DESCANSO] => POSICION
        $asignaciones = [];
        foreach ($filas as $f) {
            $asignaciones[$f->DIA_CICLO][$f->DESCANSO] = $f->POSICION;
        }

        // Posiciones asignadas HOY (para resaltar en el mapa)
        $posHoy = [];
        if ($diaHoy) {
            for ($d = 1; $d <= 2; $d++) {
                if (!empty($asignaciones[$diaHoy][$d])) {
                    $posHoy[$d] = strtoupper($asignaciones[$diaHoy][$d]);
                }
            }
        }

        // Parsear ambos KML y pasar coordenadas al mapa
        $puntosA = $this->parsearKml('Posiciones Sede A.kml', 'A');
        $puntosB = $this->parsearKml('Posiciones Sede B.kml', 'B');
        $puntosMapa = array_merge($puntosA, $puntosB);

        return view('vigilancias.docente', compact(
            'asignaciones', 'diaHoy', 'anio', 'posHoy', 'puntosMapa'
        ));
    }

    private function parsearKml(string $archivo, string $sede): array
    {
        $path = public_path("kml/{$archivo}");
        if (!file_exists($path)) return [];

        $dom = new \DOMDocument();
        @$dom->load($path);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('kml', 'http://www.opengis.net/kml/2.2');

        $puntos = [];
        $placemarks = $xpath->query('//kml:Placemark');

        foreach ($placemarks as $pm) {
            $nombre = trim($xpath->evaluate('string(kml:name)', $pm));
            if (!is_numeric($nombre)) continue;

            $coordStr = trim($xpath->evaluate('string(kml:Point/kml:coordinates)', $pm));
            if (!$coordStr) continue;

            [$lng, $lat] = array_map('floatval', explode(',', $coordStr));
            $desc = trim(strip_tags($xpath->evaluate('string(kml:description)', $pm)));

            $puntos[] = [
                'id'     => $nombre . $sede,
                'numero' => (int) $nombre,
                'sede'   => $sede,
                'lat'    => $lat,
                'lng'    => $lng,
                'desc'   => $desc,
            ];
        }

        return $puntos;
    }

    // Vista admin: gestión de asignaciones y calendario
    public function admin(Request $request)
    {
        $anio = (int) ($request->input('anio', date('Y')));

        $docentes = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        $filas = DB::table('vigilancias')
            ->where('ANIO', $anio)
            ->get();

        // Matriz [CODIGO_DOC][DIA_CICLO][DESCANSO] => POSICION
        $matriz = [];
        foreach ($filas as $f) {
            $matriz[$f->CODIGO_DOC][$f->DIA_CICLO][$f->DESCANSO] = $f->POSICION;
        }

        $calendario = DB::table('calendario_vigilancias')
            ->where('anio', $anio)
            ->orderBy('fecha')
            ->get();

        return view('vigilancias.admin', compact('docentes', 'matriz', 'calendario', 'anio'));
    }

    // Guarda toda la tabla de asignaciones (admin)
    public function guardarAsignaciones(Request $request)
    {
        $anio = (int) $request->input('anio', date('Y'));
        $data = $request->input('asignaciones', []);

        foreach ($data as $codigoDoc => $dias) {
            foreach ($dias as $dia => $descansos) {
                foreach ($descansos as $descanso => $posicion) {
                    $posicion = strtoupper(trim($posicion));

                    $existe = DB::table('vigilancias')
                        ->where('CODIGO_DOC', $codigoDoc)
                        ->where('DIA_CICLO', $dia)
                        ->where('DESCANSO', $descanso)
                        ->where('ANIO', $anio)
                        ->exists();

                    if ($posicion === '') {
                        DB::table('vigilancias')
                            ->where('CODIGO_DOC', $codigoDoc)
                            ->where('DIA_CICLO', $dia)
                            ->where('DESCANSO', $descanso)
                            ->where('ANIO', $anio)
                            ->delete();
                    } elseif ($existe) {
                        DB::table('vigilancias')
                            ->where('CODIGO_DOC', $codigoDoc)
                            ->where('DIA_CICLO', $dia)
                            ->where('DESCANSO', $descanso)
                            ->where('ANIO', $anio)
                            ->update(['POSICION' => $posicion, 'updated_at' => now()]);
                    } else {
                        DB::table('vigilancias')->insert([
                            'CODIGO_DOC'  => $codigoDoc,
                            'DIA_CICLO'   => $dia,
                            'DESCANSO'    => $descanso,
                            'POSICION'    => $posicion,
                            'ANIO'        => $anio,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
            }
        }

        return back()->with('success', 'Asignaciones guardadas correctamente.');
    }

    // Agrega o actualiza una fecha en el calendario (admin)
    public function guardarCalendario(Request $request)
    {
        $request->validate([
            'fecha'     => 'required|date',
            'dia_ciclo' => 'required|integer|min:1|max:6',
        ]);

        $anio  = (int) date('Y', strtotime($request->fecha));
        $fecha = $request->fecha;
        $dia   = (int) $request->dia_ciclo;

        $existe = DB::table('calendario_vigilancias')->where('fecha', $fecha)->exists();

        if ($existe) {
            DB::table('calendario_vigilancias')
                ->where('fecha', $fecha)
                ->update(['dia_ciclo' => $dia, 'updated_at' => now()]);
        } else {
            DB::table('calendario_vigilancias')->insert([
                'fecha'      => $fecha,
                'dia_ciclo'  => $dia,
                'anio'       => $anio,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success_cal', 'Fecha guardada correctamente.');
    }

    // Elimina una fecha del calendario (admin)
    public function eliminarCalendario(int $id)
    {
        DB::table('calendario_vigilancias')->where('id', $id)->delete();
        return back()->with('success_cal', 'Fecha eliminada.');
    }
}
