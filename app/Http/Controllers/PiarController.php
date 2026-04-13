<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiarController extends Controller
{
    // ── Detecta el período académico actual ──────────────────────────────────
    private function periodoActual(): int
    {
        // Fallback: período más alto registrado en planilla_columnas
        $p = DB::table('planilla_columnas')->orderByDesc('periodo')->value('periodo');
        if ($p) return min((int) $p, 3); // PIAR solo tiene P1, P2, P3

        // Fallback por mes
        $mes = (int) now()->format('n');
        if ($mes <= 3)  return 1;
        if ($mes <= 7)  return 2;
        return 3;
    }

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

        $estudiantesEnPiar = DB::table('PIAR_DIAG as pd')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'pd.CODIGO_ALUM')
            ->where('e.ESTADO', 'MATRICULADO')
            ->select('e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                     'e.GRADO', 'e.CURSO', 'pd.DIAGNOSTICO',
                     'pd.FECHA_P1', 'pd.PERSONA_P1',
                     'pd.FECHA_P2', 'pd.PERSONA_P2',
                     'pd.FECHA_P3', 'pd.PERSONA_P3')
            ->orderBy('e.GRADO')->orderBy('e.CURSO')->orderBy('e.APELLIDO1')
            ->get();

        $totalEnPiar   = $estudiantesEnPiar->count();
        $periodoActual = $this->periodoActual();

        return view('piar.buscar', compact('estudiantes', 'buscar', 'hayBusqueda', 'estudiantesEnPiar', 'totalEnPiar', 'periodoActual'));
    }

    public function crear(string $codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        $padres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();
        $piar   = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();

        $estudiantesEnPiar = DB::table('PIAR_DIAG as pd')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'pd.CODIGO_ALUM')
            ->where('e.ESTADO', 'MATRICULADO')
            ->select('e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                     'e.GRADO', 'e.CURSO', 'pd.DIAGNOSTICO',
                     'pd.FECHA_P1', 'pd.PERSONA_P1',
                     'pd.FECHA_P2', 'pd.PERSONA_P2',
                     'pd.FECHA_P3', 'pd.PERSONA_P3')
            ->orderBy('e.GRADO')->orderBy('e.CURSO')->orderBy('e.APELLIDO1')
            ->get();

        $totalEnPiar   = $estudiantesEnPiar->count();
        $periodoActual = $this->periodoActual();

        return view('piar.crear', compact('estudiante', 'padres', 'piar', 'estudiantesEnPiar', 'totalEnPiar', 'periodoActual'));
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

    public function imprimirTodos(string $codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        // ── Datos Anexo 1 ────────────────────────────────────────────────────
        $padres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();
        $piar   = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();

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
        $telPadres = ''; $correoPadres = '';
        $nombreMadre = $padres->MADRE     ?? ''; $nombrePadre = $padres->PADRE     ?? '';
        $empMadre    = $padres->EMP_MADRE ?? ''; $empPadre    = $padres->EMP_PADRE ?? '';
        $celMadre    = $padres->CEL_MADRE ?? ''; $emailMadre  = $padres->EMAIL_MADRE ?? '';
        $celPadre    = $padres->CEL_PADRE ?? '';
        $nombreAcud  = $padres->ACUD      ?? $nombreMadre;
        $celAcud     = $padres->CEL_ACUD  ?? '';
        $emailAcud   = $padres->EMAIL_ACUD ?? $emailMadre;
        if ($padres) {
            $telPadres    = $padres->CEL_ACUD ?: ($padres->CEL_MADRE ?: ($padres->CEL_PADRE ?: ''));
            $correoPadres = $padres->EMAIL_ACUD ?: ($padres->EMAIL_MADRE ?: ($padres->EMAIL_PADRE ?: ''));
        }

        // ── Datos Anexo 2 ────────────────────────────────────────────────────
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();

        $caractDir = DB::table('PIAR_CARACT_DIR as pcd')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'pcd.CODIGO_DOC')
            ->where('pcd.CODIGO_ALUM', $codigo)
            ->select('pcd.CARACTERIZACION', 'pcd.CURSO', 'd.NOMBRE_DOC')
            ->first();

        $matsExcluidas = [24, 124, 153]; // Urbanidad y Cívica, Urbanidad y Cívica PE, Pensamiento Lógico

        $caractMats = DB::table('PIAR_CARACT_MAT as pc')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pc.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'pc.CODIGO_DOC')
            ->where('pc.CODIGO_ALUM', $codigo)
            ->whereNotIn('pc.CODIGO_MAT', $matsExcluidas)
            ->select('pc.CARACTERIZACION', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('m.NOMBRE_MAT')
            ->get();

        $ajustes = DB::table('PIAR_MAT as pm')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pm.CODIGO_MAT')
            ->join(DB::raw('(SELECT CODIGO_MAT, CURSO, MIN(CODIGO_DOC) AS CODIGO_DOC FROM ASIGNACION_PCM GROUP BY CODIGO_MAT, CURSO) as a'), function ($j) use ($estudiante) {
                $j->on('a.CODIGO_MAT', '=', 'pm.CODIGO_MAT')->where('a.CURSO', '=', $estudiante->CURSO);
            })
            ->join('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'a.CODIGO_DOC')
            ->where('pm.CODIGO_ALUM', $codigo)
            ->whereNotIn('pm.CODIGO_MAT', $matsExcluidas)
            ->select('pm.*', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('m.NOMBRE_MAT')
            ->get();

        $docentesElaboran = collect();
        if ($caractDir) {
            $docentesElaboran->push((object)['NOMBRE_DOC' => $caractDir->NOMBRE_DOC, 'CARGO' => 'Director(a) de grupo ' . ($caractDir->CURSO ?? '')]);
        }
        foreach ($caractMats as $cm) {
            if (!$docentesElaboran->contains('NOMBRE_DOC', $cm->NOMBRE_DOC))
                $docentesElaboran->push((object)['NOMBRE_DOC' => $cm->NOMBRE_DOC, 'CARGO' => 'Docente de ' . $cm->NOMBRE_MAT]);
        }
        foreach ($ajustes as $aj) {
            if (!$docentesElaboran->contains('NOMBRE_DOC', $aj->NOMBRE_DOC))
                $docentesElaboran->push((object)['NOMBRE_DOC' => $aj->NOMBRE_DOC, 'CARGO' => 'Docente de ' . $aj->NOMBRE_MAT]);
        }

        return view('piar.imprimir_todos', compact(
            'estudiante', 'piar', 'piarDiag',
            'nombreCompleto', 'apellidos', 'tipoDoc', 'numId', 'fechaNac',
            'edad', 'grado', 'curso', 'sede', 'lugarNac', 'direccion', 'barrio',
            'epsEst', 'enferEst', 'telPadres', 'correoPadres',
            'nombreMadre', 'nombrePadre', 'empMadre', 'empPadre',
            'celMadre', 'emailMadre', 'celPadre', 'nombreAcud', 'celAcud', 'emailAcud',
            'caractDir', 'caractMats', 'ajustes', 'docentesElaboran'
        ));
    }

    public function eliminar(string $codigo)
    {
        DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->delete();

        return redirect()->route('piar.buscar')
            ->with('piar_deleted', 'El PIAR del estudiante fue eliminado correctamente.');
    }

    public function guardar(Request $request, string $codigo)
    {
        $bool = fn($v) => $v === 'si' ? 1 : 0;

        $user = auth()->user()->USER;

        // Conservar el orientador original si ya existe; asignar el usuario actual si es nuevo registro
        $oriExistente = DB::table('PIAR_DIAG')
            ->where('CODIGO_ALUM', $codigo)
            ->value('codigo_ori');
        $codigoOri = $oriExistente ?? $user;

        try {
        DB::table('PIAR_DIAG')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo],
            [
                'codigo_ori'       => $codigoOri,
                // Encabezado
                'DIAGNOSTICO'      => $request->DIAGNOSTICO,
                'LUGAR_DIL'        => $request->LUGAR_DIL,
                'PERSONA_DIL'      => $request->PERSONA_DIL,
                // Datos personales editables del estudiante
                'ALU_NOMBRES'      => $request->ALU_NOMBRES,
                'ALU_APELLIDOS'    => $request->ALU_APELLIDOS,
                'ALU_TIPO_DOC'     => $request->ALU_TIPO_DOC,
                'ALU_TIPO_DOC_OTRO'=> $request->ALU_TIPO_DOC_OTRO,
                'ALU_NUM_ID'       => $request->ALU_NUM_ID,
                'ALU_FECH_NAC'     => $request->ALU_FECH_NAC,
                'ALU_EDAD'         => $request->ALU_EDAD,
                'ALU_LUG_NAC'      => $request->ALU_LUG_NAC,
                'ALU_CURSO_INFO'   => $request->ALU_CURSO_INFO,
                'ALU_DEPTO'        => $request->ALU_DEPTO,
                'ALU_DIRECCION'    => $request->ALU_DIRECCION,
                'ALU_BARRIO'       => $request->ALU_BARRIO,
                'ALU_ESTRATO'      => $request->ALU_ESTRATO,
                'ALU_RH'           => $request->ALU_RH,
                'ALU_ALERG'        => $request->ALU_ALERG,
                'ALU_GAFAS'        => $request->ALU_GAFAS === 'si' ? 1 : 0,
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
                'TERAP_WHICH4'     => $request->TERAP_WHICH4,
                'TERAP_FREC4'      => $request->TERAP_FREC4,
                'TERAP_WHICH5'     => $request->TERAP_WHICH5,
                'TERAP_FREC5'      => $request->TERAP_FREC5,
                'ENFERPAR'         => $bool($request->ENFERPAR),
                'ENFERPAR_WHICH'   => $request->ENFERPAR_WHICH,
                'MEDIC'            => $bool($request->MEDIC),
                'MEDIC_FREC'       => $request->MEDIC_FREC,
                'MOVILID'          => $bool($request->MOVILID),
                'MOVILID_WHICH'    => $request->MOVILID_WHICH,
                // Sección 3
                'PAD_MADRE'        => $request->PAD_MADRE,
                'PAD_PADRE'        => $request->PAD_PADRE,
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
                'INSTITUPREV'          => $bool($request->INSTITUPREV),
                'INTITUPREV_WHICH'     => $request->INTITUPREV_WHICH,
                'INSTITUPREV_PORQUE'   => $request->INSTITUPREV_PORQUE,
                'ULTGRADO'         => $request->ULTGRADO,
                'APRUEBA'          => $bool($request->APRUEBA),
                'APRUEBA_PORQUE'   => $request->APRUEBA_PORQUE,
                'OBSERV'           => $request->OBSERV,
                'INFOPIAR'         => $bool($request->INFOPIAR),
                'INFOPIAR_WHICH'   => $request->INFOPIAR_WHICH,
                'COMPLEM'          => $bool($request->COMPLEM),
                'COMPLEM_WHICH'    => $request->COMPLEM_WHICH,
                'TRANSPOR'         => $request->TRANSPOR,
                'DISTANCIA'        => $request->DISTANCIA,
                'INST_NOMBRE'      => $request->INST_NOMBRE,
                'INST_SEDE'        => $request->INST_SEDE,
                // Seguimiento por período
                'FECHA_P1'         => $request->FECHA_P1 ?: null,
                'PERSONA_P1'       => $request->PERSONA_P1 ?: null,
                'FECHA_P2'         => $request->FECHA_P2 ?: null,
                'PERSONA_P2'       => $request->PERSONA_P2 ?: null,
                'FECHA_P3'         => $request->FECHA_P3 ?: null,
                'PERSONA_P3'       => $request->PERSONA_P3 ?: null,
            ]
        );

        } catch (\Exception $e) {
            return back()->with('piar_error', $e->getMessage());
        }

        return back()->with('piar_saved', 'PIAR guardado correctamente.');
    }

    public function informe()
    {
        $periodoActual = $this->periodoActual();

        // Todos los estudiantes matriculados con PIAR_DIAG registrado
        $estudiantes = DB::table('ESTUDIANTES as e')
            ->join('PIAR_DIAG as pd', 'pd.CODIGO_ALUM', '=', 'e.CODIGO')
            ->where('e.ESTADO', 'MATRICULADO')
            ->select('e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                     'e.GRADO', 'e.CURSO', 'pd.DIAGNOSTICO',
                     DB::raw("CASE WHEN pd.LUGAR_DIL IS NOT NULL AND pd.LUGAR_DIL != '' THEN 1 ELSE 0 END as ANEXO1_OK"),
                     'pd.FECHA_P1', 'pd.PERSONA_P1',
                     'pd.FECHA_P2', 'pd.PERSONA_P2',
                     'pd.FECHA_P3', 'pd.PERSONA_P3')
            ->orderBy('e.CODIGO')
            ->get();

        // Materias asignadas por curso y su estado en PIAR_MAT
        $codigoAlums = $estudiantes->pluck('CODIGO')->toArray();

        // Materias excluidas del PIAR (no aplican caracterización ni ajustes)
        $matsExcluidas = [24, 124, 153]; // Urbanidad y Cívica, Urbanidad y Cívica PE, Pensamiento Lógico

        // Todas las materias asignadas a los cursos de esos estudiantes (orden alfabético)
        $asignaciones = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->join('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'a.CODIGO_DOC')
            ->select('a.CURSO', 'a.CODIGO_MAT', 'a.CODIGO_DOC', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->whereNotIn('a.CODIGO_MAT', $matsExcluidas)
            ->orderBy('m.NOMBRE_MAT')
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
            ->select('pcd.CODIGO_ALUM', 'pcd.CARACTERIZACION', 'pcd.ESTADO',
                     'pcd.APROBADO_POR', 'pcd.FECHA_APROBACION', 'd.NOMBRE_DOC')
            ->get()
            ->groupBy('CODIGO_ALUM');

        return view('piar.informe', compact('estudiantes', 'asignaciones', 'piarMats', 'caractMats', 'caractDirs', 'periodoActual'));
    }
}
