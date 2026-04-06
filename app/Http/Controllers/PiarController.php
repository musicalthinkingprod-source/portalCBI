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
}
