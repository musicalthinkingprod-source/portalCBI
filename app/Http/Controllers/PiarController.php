<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiarController extends Controller
{
    public function buscar(Request $request)
    {
        $buscar      = $request->input('buscar');
        $estudiantes = collect();
        $hayBusqueda = $request->filled('buscar');

        if ($hayBusqueda) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('ESTADO', 'MATRICULADO')
                ->where(function ($q) use ($buscar) {
                    $q->where('CODIGO',    'like', "%$buscar%")
                      ->orWhere('NOMBRE1',   'like', "%$buscar%")
                      ->orWhere('NOMBRE2',   'like', "%$buscar%")
                      ->orWhere('APELLIDO1', 'like', "%$buscar%")
                      ->orWhere('APELLIDO2', 'like', "%$buscar%");
                })
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->paginate(20)
                ->withQueryString();
        }

        return view('piar.buscar', compact('estudiantes', 'buscar', 'hayBusqueda'));
    }

    public function crear(string $codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        $padres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();
        $piar   = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();

        return view('piar.crear', compact('estudiante', 'padres', 'piar'));
    }

    public function imprimir(string $codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        $padres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();
        $piar   = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();

        // Variables calculadas (igual que en crear)
        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");

        $tipoDoc = 'TI';
        $numId   = $estudiante->TAR_ID ?? '';
        if (!$numId && ($estudiante->REG_CIVIL ?? '')) { $tipoDoc = 'RC'; $numId = $estudiante->REG_CIVIL; }

        $fechaNac = '';
        if ($estudiante->FECH_NACIMIENTO ?? null) {
            try { $fechaNac = \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->translatedFormat('d \d\e F \d\e Y'); }
            catch (\Exception $e) { $fechaNac = $estudiante->FECH_NACIMIENTO; }
        }

        $edad      = $estudiante->EDAD ?? '';
        $grado     = $estudiante->GRADO ?? '';
        $curso     = $estudiante->CURSO ?? '';
        $sede      = $estudiante->SEDE  ? 'Sede ' . $estudiante->SEDE : '';
        $lugarNac  = $estudiante->LUG_NACIMIENTO ?? '';
        $direccion = $estudiante->DIRECCION ?? '';
        $barrio    = $estudiante->BARRIO ?? '';
        $epsEst    = $estudiante->EPS ?? '';
        $enferEst  = $estudiante->ENFER ?? '';

        $telPadres    = '';
        $correoPadres = '';
        $nombreMadre  = $padres->MADRE     ?? '';
        $nombrePadre  = $padres->PADRE     ?? '';
        $empMadre     = $padres->EMP_MADRE ?? '';
        $empPadre     = $padres->EMP_PADRE ?? '';
        $celMadre     = $padres->CEL_MADRE ?? '';
        $emailMadre   = $padres->EMAIL_MADRE ?? '';
        $celPadre     = $padres->CEL_PADRE  ?? '';
        $nombreAcud   = $padres->ACUD       ?? $nombreMadre;
        $celAcud      = $padres->CEL_ACUD   ?? '';
        $emailAcud    = $padres->EMAIL_ACUD ?? $emailMadre;
        if ($padres) {
            $telPadres    = $padres->CEL_ACUD ?: ($padres->CEL_MADRE ?: ($padres->CEL_PADRE ?: ''));
            $correoPadres = $padres->EMAIL_ACUD ?: ($padres->EMAIL_MADRE ?: ($padres->EMAIL_PADRE ?: ''));
        }

        return view('piar.imprimir_anexo1', compact(
            'estudiante', 'piar',
            'nombreCompleto', 'apellidos', 'tipoDoc', 'numId', 'fechaNac',
            'edad', 'grado', 'curso', 'sede', 'lugarNac', 'direccion', 'barrio',
            'epsEst', 'enferEst',
            'telPadres', 'correoPadres',
            'nombreMadre', 'nombrePadre', 'empMadre', 'empPadre',
            'celMadre', 'emailMadre', 'celPadre',
            'nombreAcud', 'celAcud', 'emailAcud'
        ));
    }

    public function guardar(Request $request, string $codigo)
    {
        $bool = fn($v) => $v === 'si' ? 1 : 0;

        DB::table('PIAR_DIAG')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo],
            [
                // Encabezado
                'DIAGNOSTICO'      => $request->DIAGNOSTICO,
                'LUGAR_DIL'        => $request->LUGAR_DIL,
                'PERSONA_DIL'      => $request->PERSONA_DIL,
                // Sección 1
                'MUNICIPIO'        => $request->MUNICIPIO,
                'TELEFONO'         => preg_replace('/\D/', '', $request->TELEFONO ?? '') ?: null,
                'EMAIL'            => $request->EMAIL,
                'PROTEC'           => $bool($request->PROTEC),
                'PROTEC_WHICH'     => $request->PROTEC_WHICH,
                'ASPIRA'           => $request->ASPIRA,
                'REGIS'            => $request->REGIS,
                'ETNIC'            => $bool($request->ETNIC),
                'ETNIC_WHICH'      => $request->ETNIC_WHICH,
                'CONFARM'          => $bool($request->CONFARM),
                'CONFARM_REG'      => $request->CONFARM_REG,
                // Sección 2
                'SALUD'            => $bool($request->SALUD),
                'EPS'              => $request->EPS,
                'CONT'             => $request->CONT === 'contributivo' ? 1 : 0,
                'EMERG'            => $request->EMERG,
                'PROTEGIDO'        => $bool($request->PROTEGIDO),
                'FREC_PROTEG'      => $request->FREC_PROTEG,
                'DIAGMED'          => $bool($request->DIAGMED),
                'DIAGMED_WHICH'    => $request->DIAGMED_WHICH,
                'TERAP'            => $bool($request->TERAP),
                'TERAP_WHICH1'     => $request->TERAP_WHICH1,
                'TERAP_FREC1'      => $request->TERAP_FREC1,
                'TERAP_WHICH2'     => $request->TERAP_WHICH2,
                'TERAP_FREC2'      => $request->TERAP_FREC2,
                'TERAP_WHICH3'     => $request->TERAP_WHICH3,
                'TERAP_FREC3'      => $request->TERAP_FREC3,
                'ENFERPAR'         => $bool($request->ENFERPAR),
                'ENFERPAR_WHICH'   => $request->ENFERPAR_WHICH,
                'MEDIC'            => $bool($request->MEDIC),
                'MEDIC_FREC'       => $request->MEDIC_FREC,
                'MOVILID'          => $bool($request->MOVILID),
                'MOVILID_WHICH'    => $request->MOVILID_WHICH,
                // Sección 3
                'OCUP_MADRE'       => $request->OCUP_MADRE,
                'OCUP_PADRE'       => $request->OCUP_PADRE,
                'EDUC_MADRE'       => $request->EDUC_MADRE,
                'EDUC_PADRE'       => $request->EDUC_PADRE,
                'NOMB_CUID'        => $request->NOMB_CUID,
                'PAREN_CUID'       => $request->PAREN_CUID,
                'EDUC_CUID'        => $request->EDUC_CUID,
                'TEL_CUID'         => preg_replace('/\D/', '', $request->TEL_CUID ?? '') ?: null,
                'EMAIL_CUID'       => $request->EMAIL_CUID,
                'HERMANOS'         => is_numeric($request->HERMANOS) ? (int) $request->HERMANOS : null,
                'LUGAR'            => is_numeric($request->LUGAR) ? (int) $request->LUGAR : null,
                'CRIANZA'          => $request->CRIANZA,
                'PERS_VIVE'        => $request->PERS_VIVE,
                'HOG_PROTEC'       => $bool($request->HOG_PROTEC),
                'HOG_PROTEC_WHICH' => $request->HOG_PROTEC_WHICH,
                'HOG_SUB'          => $bool($request->HOG_SUB),
                'HOG_SUB_WHICH'    => $request->HOG_SUB_WHICH,
                // Sección 4
                'INSTITUPREV'      => $bool($request->INSTITUPREV),
                'INTITUPREV_WHICH' => $request->INTITUPREV_WHICH,
                'ULTGRADO'         => $request->ULTGRADO,
                'APRUEBA'          => $bool($request->APRUEBA),
                'OBSERV'           => $request->OBSERV,
                'INFOPIAR'         => $bool($request->INFOPIAR),
                'INFOPIAR_WHICH'   => $request->INFOPIAR_WHICH,
                'COMPLEM'          => $bool($request->COMPLEM),
                'COMPLEM_WHICH'    => $request->COMPLEM_WHICH,
                'TRANSPOR'         => $request->TRANSPOR,
                'DISTANCIA'        => $request->DISTANCIA,
            ]
        );

        return back()->with('piar_saved', 'PIAR guardado correctamente.');
    }

    public function informe()
    {
        // Todos los estudiantes matriculados con PIAR_DIAG registrado
        $estudiantes = DB::table('ESTUDIANTES as e')
            ->join('PIAR_DIAG as pd', 'pd.CODIGO_ALUM', '=', 'e.CODIGO')
            ->where('e.ESTADO', 'MATRICULADO')
            ->select('e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                     'e.GRADO', 'e.CURSO', 'pd.DIAGNOSTICO',
                     DB::raw("CASE WHEN pd.LUGAR_DIL IS NOT NULL AND pd.LUGAR_DIL != '' THEN 1 ELSE 0 END as ANEXO1_OK"))
            ->orderBy('e.GRADO')->orderBy('e.CURSO')->orderBy('e.APELLIDO1')
            ->get();

        // Materias asignadas por curso y su estado en PIAR_MAT
        $codigoAlums = $estudiantes->pluck('CODIGO')->toArray();

        // Todas las materias asignadas a los cursos de esos estudiantes
        $asignaciones = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->join('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'a.CODIGO_DOC')
            ->select('a.CURSO', 'a.CODIGO_MAT', 'a.CODIGO_DOC', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->get()
            ->groupBy('CURSO');

        // Registros PIAR_MAT existentes
        $piarMats = DB::table('PIAR_MAT')
            ->whereIn('CODIGO_ALUM', $codigoAlums)
            ->get()
            ->groupBy('CODIGO_ALUM')
            ->map(fn($rows) => $rows->keyBy('CODIGO_MAT'));

        // Caracterizaciones por materia
        $caractMats = DB::table('PIAR_CARACT_MAT')
            ->whereIn('CODIGO_ALUM', $codigoAlums)
            ->get()
            ->groupBy('CODIGO_ALUM')
            ->map(fn($rows) => $rows->keyBy('CODIGO_MAT'));

        // Caracterizaciones por director de grupo
        $caractDirs = DB::table('PIAR_CARACT_DIR as pcd')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'pcd.CODIGO_DOC')
            ->whereIn('pcd.CODIGO_ALUM', $codigoAlums)
            ->select('pcd.CODIGO_ALUM', 'pcd.CARACTERIZACION', 'd.NOMBRE_DOC')
            ->get()
            ->groupBy('CODIGO_ALUM');

        return view('piar.informe', compact('estudiantes', 'asignaciones', 'piarMats', 'caractMats', 'caractDirs'));
    }
}
