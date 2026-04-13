<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $estudiantes = collect();
        $hayBusqueda = $request->anyFilled(['buscar', 'grado', 'curso', 'sede', 'estado', 'email_padre']);

        if ($hayBusqueda) {
            $query = DB::table('ESTUDIANTES as e')
                ->leftJoin('INFO_PADRES as p', 'e.CODIGO', '=', 'p.CODIGO')
                ->select('e.*');

            if ($request->filled('buscar')) {
                $b = $request->buscar;
                $query->where(function($q) use ($b) {
                    $q->where('e.CODIGO', 'like', "%$b%")
                      ->orWhere('e.NOMBRE1', 'like', "%$b%")
                      ->orWhere('e.NOMBRE2', 'like', "%$b%")
                      ->orWhere('e.APELLIDO1', 'like', "%$b%")
                      ->orWhere('e.APELLIDO2', 'like', "%$b%");
                });
            }

            if ($request->filled('grado'))       $query->where('e.GRADO', $request->grado);
            if ($request->filled('curso'))        $query->where('e.CURSO', $request->curso);
            if ($request->filled('sede'))         $query->where('e.SEDE', $request->sede);
            if ($request->filled('estado'))       $query->where('e.ESTADO', $request->estado);
            if ($request->filled('email_padre')) {
                $em = $request->email_padre;
                $query->where(function($q) use ($em) {
                    $q->where('p.EMAIL_MADRE', 'like', "%$em%")
                      ->orWhere('p.EMAIL_PADRE', 'like', "%$em%")
                      ->orWhere('p.EMAIL_ACUD', 'like', "%$em%");
                });
            }

            // Matriculados primero, luego ordenar por apellidos y nombres
            $estudiantes = $query
                ->orderByRaw("CASE WHEN e.ESTADO = 'MATRICULADO' THEN 0 ELSE 1 END")
                ->orderBy('e.APELLIDO1')
                ->orderBy('e.APELLIDO2')
                ->orderBy('e.NOMBRE1')
                ->orderBy('e.NOMBRE2')
                ->paginate(20)
                ->withQueryString();
        }

        // Opciones para filtros
        $grados = DB::table('ESTUDIANTES')->select('GRADO')->distinct()->whereNotNull('GRADO')
            ->orderByRaw('CAST(GRADO AS SIGNED)')->pluck('GRADO');
        $cursos  = DB::table('ESTUDIANTES')->select('CURSO')->distinct()->whereNotNull('CURSO')->orderBy('CURSO')->pluck('CURSO');
        $sedes   = DB::table('ESTUDIANTES')->select('SEDE')->distinct()->whereNotNull('SEDE')->orderBy('SEDE')->pluck('SEDE');
        $estados = DB::table('ESTUDIANTES')->select('ESTADO')->distinct()->whereNotNull('ESTADO')->orderBy('ESTADO')->pluck('ESTADO');

        return view('alumnos.index', compact('estudiantes', 'hayBusqueda', 'grados', 'cursos', 'sedes', 'estados'));
    }

    public function show($codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

        if (!$estudiante) {
            return redirect()->route('alumnos.index')->withErrors(['buscar' => 'Estudiante no encontrado.']);
        }

        $infoPadres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();
        $infoAcadem = DB::table('INFO_ACADEM')->where('CODIGO', $codigo)->first();
        $edad       = $estudiante->FECH_NACIMIENTO
            ? \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->age
            : null;

        return view('alumnos.show', compact('estudiante', 'infoPadres', 'infoAcadem', 'edad'));
    }

    public function create()
    {
        return view('alumnos.create');
    }

    public function store(Request $request)
    {
        $codigo = $request->CODIGO;

        DB::table('ESTUDIANTES')->insert([
            'CODIGO'         => $codigo,
            'NOMBRE1'        => $request->NOMBRE1,
            'NOMBRE2'        => $request->NOMBRE2 ?? '',
            'APELLIDO1'      => $request->APELLIDO1,
            'APELLIDO2'      => $request->APELLIDO2 ?? '',
            'GRADO'          => $request->filled('GRADO') ? (int) $request->GRADO : null,
            'CURSO'          => $request->CURSO,
            'SEDE'           => $request->SEDE,
            'ESTADO'         => $request->ESTADO,
            'FECH_NACIMIENTO'=> $request->FECH_NACIMIENTO ?: null,
            'LUG_NACIMIENTO' => $request->LUG_NACIMIENTO,
            'LUG_EXPED'      => $request->LUG_EXPED,
            'TAR_ID'         => $request->TAR_ID,
            'REG_CIVIL'      => $request->REG_CIVIL,
            'RH'             => $request->RH,
            'EPS'            => $request->EPS,
            'ALERG'          => $request->ALERG,
            'ENFER'          => $request->ENFER,
            'GAFAS'          => $request->GAFAS,
            'DIRECCION'      => $request->DIRECCION,
            'BARRIO'         => $request->BARRIO,
            'ESTRATO'        => $request->ESTRATO,
            'ACUDIENTE'      => $request->ACUDIENTE,
            'ENTRADA'        => $request->ENTRADA,
            'SALIDA'         => $request->SALIDA,
        ]);

        $padreFields = ['MADRE','CC_MADRE','CEL_MADRE','EMAIL_MADRE','PADRE','CC_PADRE','CEL_PADRE','EMAIL_PADRE','ACUD','CC_ACUD','CEL_ACUD','EMAIL_ACUD'];
        $padreData = [];
        foreach ($padreFields as $f) {
            $padreData[$f] = $request->input($f);
        }
        if (array_filter($padreData)) {
            DB::table('INFO_PADRES')->insert(array_merge(['CODIGO' => $codigo], $padreData));
        }

        $academData = [];
        foreach (['PJ','J','T','1','2','3','4','5','6','7','8','9','10','11'] as $nivel) {
            $academData["INS_$nivel"] = $request->input("INS_$nivel");
            $academData["ANO_$nivel"] = $request->input("ANO_$nivel");
        }
        if (array_filter($academData)) {
            DB::table('INFO_ACADEM')->insert(array_merge(['CODIGO' => $codigo], $academData));
        }

        return redirect()->route('alumnos.show', $codigo)->with('success', 'Estudiante matriculado correctamente.');
    }

    public function printList(Request $request)
    {
        $hayBusqueda = $request->anyFilled(['buscar', 'grado', 'curso', 'sede', 'estado', 'email_padre']);

        if (!$hayBusqueda) {
            return redirect()->route('alumnos.index')->withErrors(['buscar' => 'Aplique al menos un filtro antes de imprimir.']);
        }

        $query = DB::table('ESTUDIANTES as e')
            ->leftJoin('INFO_PADRES as p', 'e.CODIGO', '=', 'p.CODIGO')
            ->select('e.CODIGO', 'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.GRADO', 'e.CURSO', 'e.ESTADO');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function($q) use ($b) {
                $q->where('e.CODIGO', 'like', "%$b%")
                  ->orWhere('e.NOMBRE1', 'like', "%$b%")
                  ->orWhere('e.NOMBRE2', 'like', "%$b%")
                  ->orWhere('e.APELLIDO1', 'like', "%$b%")
                  ->orWhere('e.APELLIDO2', 'like', "%$b%");
            });
        }

        if ($request->filled('grado'))       $query->where('e.GRADO', $request->grado);
        if ($request->filled('curso'))        $query->where('e.CURSO', $request->curso);
        if ($request->filled('sede'))         $query->where('e.SEDE', $request->sede);
        if ($request->filled('estado'))       $query->where('e.ESTADO', $request->estado);
        if ($request->filled('email_padre')) {
            $em = $request->email_padre;
            $query->where(function($q) use ($em) {
                $q->where('p.EMAIL_MADRE', 'like', "%$em%")
                  ->orWhere('p.EMAIL_PADRE', 'like', "%$em%")
                  ->orWhere('p.EMAIL_ACUD', 'like', "%$em%");
            });
        }

        switch ($request->input('orden', 'apellidos')) {
            case 'codigo':
                $query->orderBy('e.CODIGO');
                break;
            case 'grado_apellidos':
                $query->orderByRaw('CAST(e.GRADO AS SIGNED)')
                      ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')
                      ->orderBy('e.NOMBRE1')->orderBy('e.NOMBRE2');
                break;
            case 'curso_apellidos':
                $query->orderBy('e.CURSO')
                      ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')
                      ->orderBy('e.NOMBRE1')->orderBy('e.NOMBRE2');
                break;
            default: // apellidos
                $query->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')
                      ->orderBy('e.NOMBRE1')->orderBy('e.NOMBRE2');
        }

        $estudiantes = $query->get();

        $titulo = $request->input('titulo', 'Listado de Estudiantes');
        $col1   = trim($request->input('col1', ''));
        $col2   = trim($request->input('col2', ''));

        return view('alumnos.print_list', compact('estudiantes', 'titulo', 'col1', 'col2'));
    }

    public function printView($codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

        if (!$estudiante) {
            return redirect()->route('alumnos.index');
        }

        $infoPadres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();
        $infoAcadem = DB::table('INFO_ACADEM')->where('CODIGO', $codigo)->first();
        $edad       = $estudiante->FECH_NACIMIENTO
            ? \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->age
            : null;

        return view('alumnos.print', compact('estudiante', 'infoPadres', 'infoAcadem', 'edad'));
    }

    public function edit($codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        $infoPadres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();
        $infoAcadem = DB::table('INFO_ACADEM')->where('CODIGO', $codigo)->first();

        return view('alumnos.edit', compact('estudiante', 'infoPadres', 'infoAcadem'));
    }

    public function update(Request $request, $codigo)
    {
        DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->update([
            'NOMBRE1'        => $request->NOMBRE1,
            'NOMBRE2'        => $request->NOMBRE2 ?? '',
            'APELLIDO1'      => $request->APELLIDO1,
            'APELLIDO2'      => $request->APELLIDO2 ?? '',
            'GRADO'          => $request->filled('GRADO') ? (int) $request->GRADO : null,
            'CURSO'          => $request->CURSO,
            'SEDE'           => $request->SEDE,
            'ESTADO'         => $request->ESTADO,
            'FECH_NACIMIENTO'=> $request->FECH_NACIMIENTO,
            'LUG_NACIMIENTO' => $request->LUG_NACIMIENTO,
            'LUG_EXPED'      => $request->LUG_EXPED,
            'TAR_ID'         => $request->TAR_ID,
            'REG_CIVIL'      => $request->REG_CIVIL,
            'RH'             => $request->RH,
            'EPS'            => $request->EPS,
            'ALERG'          => $request->ALERG,
            'ENFER'          => $request->ENFER,
            'GAFAS'          => $request->GAFAS,
            'DIRECCION'      => $request->DIRECCION,
            'BARRIO'         => $request->BARRIO,
            'ESTRATO'        => $request->ESTRATO,
            'ACUDIENTE'      => $request->ACUDIENTE,
            'ENTRADA'        => $request->ENTRADA,
            'SALIDA'         => $request->SALIDA,
        ]);

        if (DB::table('INFO_PADRES')->where('CODIGO', $codigo)->exists()) {
            DB::table('INFO_PADRES')->where('CODIGO', $codigo)->update([
                'MADRE'        => $request->MADRE,
                'CC_MADRE'     => $request->CC_MADRE,
                'CEL_MADRE'    => $request->CEL_MADRE,
                'EMAIL_MADRE'  => $request->EMAIL_MADRE,
                'PADRE'        => $request->PADRE,
                'CC_PADRE'     => $request->CC_PADRE,
                'CEL_PADRE'    => $request->CEL_PADRE,
                'EMAIL_PADRE'  => $request->EMAIL_PADRE,
                'ACUD'         => $request->ACUD,
                'CC_ACUD'      => $request->CC_ACUD,
                'CEL_ACUD'     => $request->CEL_ACUD,
                'EMAIL_ACUD'   => $request->EMAIL_ACUD,
            ]);
        }

        $academData = [];
        foreach (['PJ','J','T','1','2','3','4','5','6','7','8','9','10','11'] as $nivel) {
            $academData["INS_$nivel"] = $request->input("INS_$nivel");
            $academData["ANO_$nivel"] = $request->input("ANO_$nivel");
        }

        if (DB::table('INFO_ACADEM')->where('CODIGO', $codigo)->exists()) {
            DB::table('INFO_ACADEM')->where('CODIGO', $codigo)->update($academData);
        } else {
            DB::table('INFO_ACADEM')->insert(array_merge(['CODIGO' => $codigo], $academData));
        }

        return redirect()->route('alumnos.show', $codigo)->with('success', 'Información actualizada correctamente.');
    }
}
