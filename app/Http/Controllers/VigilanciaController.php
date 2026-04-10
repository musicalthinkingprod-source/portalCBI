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
        $diaHoy  = DB::table('calendario_academico')
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

        // Próximo día académico con vigilancias para este docente
        $proximaFechaVig = DB::table('calendario_academico')
            ->where('fecha', '>', $hoy)
            ->where('dia_ciclo', '>', 0)
            ->whereIn('dia_ciclo', array_keys($asignaciones))
            ->orderBy('fecha')
            ->first();

        // Parsear ambos KML y pasar coordenadas al mapa
        $puntosA = $this->parsearKml('Posiciones Sede A.kml', 'A');
        $puntosB = $this->parsearKml('Posiciones Sede B.kml', 'B');
        $puntosMapa = array_merge($puntosA, $puntosB);

        return view('vigilancias.docente', compact(
            'asignaciones', 'diaHoy', 'anio', 'posHoy', 'puntosMapa', 'proximaFechaVig'
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

        $conAsignacion = DB::table('vigilancias')
            ->where('ANIO', $anio)
            ->distinct()
            ->pluck('CODIGO_DOC');

        $docentes = DB::table('CODIGOS_DOC')
            ->whereIn('CODIGO_DOC', $conAsignacion)
            ->orderBy('ESTADO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        $conAsignacionCodigos = DB::table('vigilancias')
            ->where('ANIO', $anio)
            ->distinct()
            ->pluck('CODIGO_DOC');

        $docentesDestino = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->whereNotIn('CODIGO_DOC', $conAsignacionCodigos)
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

        // Parsear KML para el mapa admin
        $puntosA = $this->parsearKml('Posiciones Sede A.kml', 'A');
        $puntosB = $this->parsearKml('Posiciones Sede B.kml', 'B');
        $puntosMapa = array_merge($puntosA, $puntosB);

        // Mapa posicion → nombre docente (para mostrar en el mapa)
        $nombresDoc = DB::table('CODIGOS_DOC')->pluck('NOMBRE_DOC', 'CODIGO_DOC');
        $posicionDocente = [];
        foreach ($filas as $f) {
            if (!$f->POSICION) continue;
            $posicionDocente[strtoupper($f->POSICION)] = [
                'docente'  => $nombresDoc[$f->CODIGO_DOC] ?? $f->CODIGO_DOC,
                'descanso' => $f->DESCANSO,
                'dia'      => $f->DIA_CICLO,
            ];
        }

        // Datos para reasignaciones
        $docentesConAsig = DB::table('vigilancias as v')
            ->leftJoin('CODIGOS_DOC as d', 'v.CODIGO_DOC', '=', 'd.CODIGO_DOC')
            ->where('v.ANIO', $anio)
            ->select(
                'v.CODIGO_DOC',
                DB::raw('COALESCE(d.NOMBRE_DOC, v.CODIGO_DOC) as NOMBRE_DOC'),
                DB::raw('COUNT(*) as total_slots')
            )
            ->groupBy('v.CODIGO_DOC', 'd.NOMBRE_DOC')
            ->orderBy('d.NOMBRE_DOC')
            ->get();

        $verDoc   = $request->input('ver_asig');
        $slotsDoc = collect();
        if ($verDoc) {
            $slotsDoc = DB::table('vigilancias')
                ->where('CODIGO_DOC', $verDoc)
                ->where('ANIO', $anio)
                ->orderBy('DIA_CICLO')->orderBy('DESCANSO')
                ->get();
        }

        $calendario = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->orderBy('fecha')
            ->get();

        return view('vigilancias.admin', compact(
            'docentes', 'docentesDestino', 'matriz', 'anio',
            'docentesConAsig', 'verDoc', 'slotsDoc',
            'puntosMapa', 'posicionDocente', 'calendario'
        ));
    }

    // Vista de control de vigilancias (supervisores con GPS)
    public function control()
    {
        $anio   = (int) date('Y');
        $hoy    = now()->toDateString();
        $diaHoy = DB::table('calendario_academico')
            ->where('fecha', $hoy)
            ->value('dia_ciclo');

        // Todas las asignaciones de hoy: posicion → {docente, descanso}
        $nombresDoc = DB::table('CODIGOS_DOC')->pluck('NOMBRE_DOC', 'CODIGO_DOC');
        $posicionDocente = [];

        if ($diaHoy) {
            $filas = DB::table('vigilancias')
                ->where('ANIO', $anio)
                ->where('DIA_CICLO', $diaHoy)
                ->get();

            foreach ($filas as $f) {
                if (!$f->POSICION) continue;
                $posicionDocente[strtoupper($f->POSICION)] = [
                    'docente'  => $nombresDoc[$f->CODIGO_DOC] ?? $f->CODIGO_DOC,
                    'descanso' => $f->DESCANSO,
                ];
            }
        }

        $puntosA    = $this->parsearKml('Posiciones Sede A.kml', 'A');
        $puntosB    = $this->parsearKml('Posiciones Sede B.kml', 'B');
        $puntosMapa = array_merge($puntosA, $puntosB);

        return view('vigilancias.control', compact('puntosMapa', 'posicionDocente', 'diaHoy', 'anio'));
    }

    // Vista de reasignaciones (estilo asignaciones de materias)
    public function reasignaciones(Request $request)
    {
        $anio = (int) ($request->input('anio', date('Y')));

        $docentesConAsig = DB::table('vigilancias as v')
            ->leftJoin('CODIGOS_DOC as d', 'v.CODIGO_DOC', '=', 'd.CODIGO_DOC')
            ->where('v.ANIO', $anio)
            ->select(
                'v.CODIGO_DOC',
                DB::raw('COALESCE(d.NOMBRE_DOC, v.CODIGO_DOC) as NOMBRE_DOC'),
                DB::raw('COUNT(*) as total_slots')
            )
            ->groupBy('v.CODIGO_DOC', 'd.NOMBRE_DOC')
            ->orderBy('d.NOMBRE_DOC')
            ->get();

        $docentesActivos = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        $verDoc    = $request->input('ver_asig');
        $slotsDoc  = collect();

        if ($verDoc) {
            $slotsDoc = DB::table('vigilancias')
                ->where('CODIGO_DOC', $verDoc)
                ->where('ANIO', $anio)
                ->orderBy('DIA_CICLO')
                ->orderBy('DESCANSO')
                ->get();
        }

        return view('vigilancias.reasignaciones', compact(
            'docentesConAsig', 'docentesActivos', 'verDoc', 'slotsDoc', 'anio'
        ));
    }

    // Reasigna un slot individual a otro docente (hace intercambio si hay conflicto)
    public function reasignarUna(Request $request)
    {
        $origen   = $request->input('origen');
        $destino  = $request->input('destino');
        $dia      = (int) $request->input('DIA_CICLO');
        $descanso = (int) $request->input('DESCANSO');
        $anio     = (int) $request->input('anio', date('Y'));

        if (!$origen || !$destino || !$dia || !$descanso) {
            return back()->withErrors(['reasig_una' => 'Faltan datos para reasignar.']);
        }
        if ($origen === $destino) {
            return back()->withErrors(['reasig_una' => 'El docente destino debe ser diferente al origen.']);
        }

        // Buscar slot origen
        $slotOrigen = DB::table('vigilancias')
            ->where('CODIGO_DOC', $origen)
            ->where('DIA_CICLO', $dia)
            ->where('DESCANSO', $descanso)
            ->where('ANIO', $anio)
            ->first();

        if (!$slotOrigen) {
            return back()->withErrors(['reasig_una' => 'No se encontró el slot a reasignar.']);
        }

        // Buscar si el destino ya tiene algo en ese slot → intercambiar
        $slotDestino = DB::table('vigilancias')
            ->where('CODIGO_DOC', $destino)
            ->where('DIA_CICLO', $dia)
            ->where('DESCANSO', $descanso)
            ->where('ANIO', $anio)
            ->first();

        if ($slotDestino) {
            // Intercambio: darle al destino la posición del origen y viceversa
            DB::table('vigilancias')->where('id', $slotOrigen->id)
                ->update(['CODIGO_DOC' => $destino, 'updated_at' => now()]);
            DB::table('vigilancias')->where('id', $slotDestino->id)
                ->update(['CODIGO_DOC' => $origen, 'updated_at' => now()]);
            $msg = "Posiciones intercambiadas entre «{$origen}» y «{$destino}» en Día {$dia} / Descanso {$descanso}.";
        } else {
            // Solo mover
            DB::table('vigilancias')->where('id', $slotOrigen->id)
                ->update(['CODIGO_DOC' => $destino, 'updated_at' => now()]);
            $msg = "Slot Día {$dia} / Descanso {$descanso} movido de «{$origen}» a «{$destino}».";
        }

        return redirect()
            ->route('vigilancias.admin', ['ver_asig' => $origen, 'anio' => $anio])
            ->with('success_reasig_una', $msg);
    }

    // Mueve / intercambia TODOS los slots entre dos docentes
    public function reasignarBloque(Request $request)
    {
        $request->validate([
            'origen'  => 'required',
            'destino' => 'required|different:origen',
        ], [
            'destino.different' => 'El docente destino debe ser diferente al origen.',
        ]);

        $origen  = $request->origen;
        $destino = $request->destino;
        $anio    = (int) $request->input('anio', date('Y'));

        $totalOrigen = DB::table('vigilancias')
            ->where('CODIGO_DOC', $origen)->where('ANIO', $anio)->count();

        if ($totalOrigen === 0) {
            return back()->withErrors(['reasig_bloque' => 'El docente origen no tiene vigilancias asignadas.']);
        }

        $tieneDestino = DB::table('vigilancias')
            ->where('CODIGO_DOC', $destino)->where('ANIO', $anio)->exists();

        if ($tieneDestino) {
            // Intercambio completo usando un código temporal para evitar conflicto de unique
            $tmp = '_TMP_' . uniqid();
            DB::table('vigilancias')->where('CODIGO_DOC', $origen)->where('ANIO', $anio)
                ->update(['CODIGO_DOC' => $tmp]);
            DB::table('vigilancias')->where('CODIGO_DOC', $destino)->where('ANIO', $anio)
                ->update(['CODIGO_DOC' => $origen]);
            DB::table('vigilancias')->where('CODIGO_DOC', $tmp)->where('ANIO', $anio)
                ->update(['CODIGO_DOC' => $destino]);
            $msg = "Vigilancias intercambiadas entre «{$origen}» y «{$destino}».";
        } else {
            DB::table('vigilancias')->where('CODIGO_DOC', $origen)->where('ANIO', $anio)
                ->update(['CODIGO_DOC' => $destino]);
            $msg = "{$totalOrigen} slot(s) movidos de «{$origen}» a «{$destino}».";
        }

        return back()->with('success_reasig_bloque', $msg);
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
            'fecha'       => 'required|date',
            'dia_ciclo'   => 'required|integer|min:1|max:6',
            'evento'      => 'nullable|string|max:200',
            'visibilidad' => 'required|in:todos,interno,docentes,directivas,padres',
        ]);

        $anio        = (int) date('Y', strtotime($request->fecha));
        $fecha       = $request->fecha;
        $dia         = (int) $request->dia_ciclo;
        $evento      = $request->input('evento');
        $visibilidad = $request->input('visibilidad', 'interno');

        $existe = DB::table('calendario_academico')->where('fecha', $fecha)->exists();

        if ($existe) {
            DB::table('calendario_academico')
                ->where('fecha', $fecha)
                ->update(['dia_ciclo' => $dia, 'evento' => $evento, 'visibilidad' => $visibilidad, 'updated_at' => now()]);
        } else {
            DB::table('calendario_academico')->insert([
                'fecha'       => $fecha,
                'dia_ciclo'   => $dia,
                'anio'        => $anio,
                'evento'      => $evento,
                'visibilidad' => $visibilidad,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        return back()->with('success_cal', 'Fecha guardada correctamente.');
    }

    // Elimina una fecha del calendario (admin)
    public function eliminarCalendario(int $id)
    {
        DB::table('calendario_academico')->where('id', $id)->delete();
        return back()->with('success_cal', 'Fecha eliminada.');
    }
}
