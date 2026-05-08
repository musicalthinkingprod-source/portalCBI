<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificadosController extends Controller
{
    private const ANIOS_DISPONIBLES = [2024, 2025];

    public function buscar(Request $request)
    {
        $anios   = self::ANIOS_DISPONIBLES;
        $defecto = $anios[count($anios) - 1];
        $anio    = (int) $request->input('anio', $defecto);
        if (!in_array($anio, $anios, true)) {
            $anio = $defecto;
        }

        $q           = trim((string) $request->input('q', ''));
        $estudiantes = collect();

        if (strlen($q) >= 2) {
            $cursoCol = "CURSO_{$anio}";

            $estudiantes = DB::table('ESTUDIANTES as e')
                ->leftJoin('ESTUDIANTES_CURSOS_ANUAL as eca', 'eca.CODIGO_ALUM', '=', 'e.CODIGO')
                ->where(function ($w) use ($q) {
                    $w->where('e.NOMBRE1',   'like', "%{$q}%")
                      ->orWhere('e.NOMBRE2',   'like', "%{$q}%")
                      ->orWhere('e.APELLIDO1', 'like', "%{$q}%")
                      ->orWhere('e.APELLIDO2', 'like', "%{$q}%")
                      ->orWhere('e.CODIGO',    'like', "%{$q}%");
                })
                ->select(
                    'e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2',
                    'e.APELLIDO1', 'e.APELLIDO2',
                    "eca.{$cursoCol} as CURSO_ANIO"
                )
                ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')->orderBy('e.NOMBRE1')
                ->get()
                ->filter(fn($e) => !empty($e->CURSO_ANIO))
                ->values();
        }

        $aniosDisponibles = self::ANIOS_DISPONIBLES;
        return view('certificados.buscar', compact('estudiantes', 'anio', 'q', 'aniosDisponibles'));
    }

    public function ver(int $codigo, Request $request)
    {
        $datos = $this->datosCertificado($codigo, (int) $request->input('anio', 0), $request->input('fecha'));
        if (empty($datos)) abort(404);

        return view('certificados.ver', $datos);
    }

    public function pdf(int $codigo, Request $request)
    {
        $datos = $this->datosCertificado($codigo, (int) $request->input('anio', 0), $request->input('fecha'));
        if (empty($datos)) abort(404);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificados.pdf', $datos)
            ->setPaper('letter');

        $apellidos = trim($datos['estudiante']->APELLIDO1 . '_' . $datos['estudiante']->APELLIDO2);
        $apellidos = preg_replace('/[^A-Za-z0-9_]/', '', $apellidos);
        $nombre    = "Certificado_{$datos['anio']}_{$codigo}_{$apellidos}.pdf";

        return $pdf->stream($nombre);
    }

    private function datosCertificado(int $codigo, int $anio, ?string $fechaInput): array
    {
        if (!in_array($anio, self::ANIOS_DISPONIBLES, true)) return [];

        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) return [];

        $eca       = DB::table('ESTUDIANTES_CURSOS_ANUAL')->where('CODIGO_ALUM', $codigo)->first();
        $cursoAnio = $eca?->{"CURSO_{$anio}"} ?? null;
        if (!$cursoAnio) return [];

        // Excluidas del certificado: Acompañamiento de Padres (32, 50, 51), Conducta (33), Disciplina (34).
        $matExcluidas = [32, 33, 34, 50, 51];

        $notasRaw = DB::table('NOTAS_' . $anio . ' as n')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'n.CODIGO_MAT')
            ->where('n.CODIGO_ALUM', $codigo)
            ->whereNotIn('n.CODIGO_MAT', $matExcluidas)
            ->select('n.CODIGO_MAT', 'm.NOMBRE_MAT', 'n.PERIODO', 'n.NOTA')
            ->get();

        $porMat = [];
        foreach ($notasRaw as $r) {
            $mk = $r->CODIGO_MAT;
            if (!isset($porMat[$mk])) {
                $porMat[$mk] = ['nombre' => $r->NOMBRE_MAT, 'notas' => []];
            }
            $porMat[$mk]['notas'][$r->PERIODO] = (float) $r->NOTA;
        }

        $ihsRaw = DB::table('ASIGNACION_PCM')
            ->where('CURSO', $cursoAnio)
            ->whereNotNull('CODIGO_MAT')
            ->pluck('IHS', 'CODIGO_MAT');

        $materias = [];
        foreach ($porMat as $codMat => $info) {
            $notas = $info['notas'];
            if (empty($notas)) continue;
            $prom  = round(array_sum($notas) / count($notas), 1);
            $nivel = match(true) {
                $prom >  9.0 => 'Superior',
                $prom >  8.0 => 'Alto',
                $prom >= 7.0 => 'Básico',
                default      => 'Bajo',
            };
            $materias[] = [
                'codigo' => $codMat,
                'nombre' => $info['nombre'],
                'ihs'    => $ihsRaw[$codMat] ?? '',
                'escala' => number_format($prom, 1, '.', ''),
                'nivel'  => $nivel,
            ];
        }

        usort($materias, fn($a, $b) => strcasecmp(
            $this->limpiarTildes($a['nombre']),
            $this->limpiarTildes($b['nombre'])
        ));

        $total = count($materias);
        $mitad = (int) ceil($total / 2);
        $colIzq = array_slice($materias, 0, $mitad);
        $colDer = array_slice($materias, $mitad);

        $gradoNum       = (int) preg_replace('/\D/', '', $cursoAnio);
        $gradoTexto     = self::gradoTexto($cursoAnio, $gradoNum);
        $nivelEducativo = self::nivelEducativo($cursoAnio, $gradoNum);

        [$tipoDoc, $numDoc] = self::documentoEstudiante($estudiante);

        $nombreCompleto = trim(implode(' ', array_filter([
            $estudiante->NOMBRE1 ?? null,
            $estudiante->NOMBRE2 ?? null,
            $estudiante->APELLIDO1 ?? null,
            $estudiante->APELLIDO2 ?? null,
        ])));

        $fecha = self::parseFecha($fechaInput) ?? now();

        $lugarExped = $estudiante->LUG_EXPED ?: 'Bogotá';

        return compact(
            'estudiante', 'nombreCompleto', 'tipoDoc', 'numDoc', 'lugarExped',
            'cursoAnio', 'gradoTexto', 'nivelEducativo', 'anio',
            'materias', 'colIzq', 'colDer', 'fecha', 'codigo'
        );
    }

    private function limpiarTildes(string $s): string
    {
        return strtr($s, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'ñ'=>'n','Ñ'=>'N',
        ]);
    }

    private static function gradoTexto(string $curso, int $num): string
    {
        $u = strtoupper(trim($curso));
        if (in_array($u, ['T', 'J', 'PJ', 'PRE', 'TRANS'])) return 'TRANSICIÓN';

        $mapa = [
            1 => 'PRIMERO', 2 => 'SEGUNDO', 3 => 'TERCERO', 4 => 'CUARTO',
            5 => 'QUINTO', 6 => 'SEXTO', 7 => 'SÉPTIMO', 8 => 'OCTAVO',
            9 => 'NOVENO', 10 => 'DÉCIMO', 11 => 'UNDÉCIMO',
        ];
        return $mapa[$num] ?? strtoupper($curso);
    }

    private static function nivelEducativo(string $curso, int $num): string
    {
        $u = strtoupper(trim($curso));
        if (in_array($u, ['T', 'J', 'PJ', 'PRE', 'TRANS'])) return 'Preescolar';
        if ($num >= 1 && $num <= 5)  return 'Básica Primaria';
        if ($num >= 6 && $num <= 9)  return 'Básica Secundaria';
        if ($num >= 10)              return 'Media Vocacional';
        return '';
    }

    private static function documentoEstudiante(object $est): array
    {
        if (!empty($est->TAR_ID))    return ['T.I.', $est->TAR_ID];
        if (!empty($est->REG_CIVIL)) return ['R.C.', $est->REG_CIVIL];
        return ['', ''];
    }

    private static function parseFecha(?string $f): ?Carbon
    {
        if (!$f) return null;
        try { return Carbon::parse($f); } catch (\Exception $e) { return null; }
    }
}
