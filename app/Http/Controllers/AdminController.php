<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        return $this->usuarios($request);
    }

    public function usuarios(Request $request)
    {
        $usuarios = DB::table('PRINUSERS')->orderBy('PROFILE')->orderBy('USER')->get();

        $docentes = DB::table('CODIGOS_DOC')
            ->orderBy('ESTADO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        $ultimoCod = DB::table('CODIGOS_DOC')
            ->where('CODIGO_DOC', 'like', 'DOC%')
            ->orderByRaw('CAST(SUBSTRING(CODIGO_DOC, 4) AS UNSIGNED) DESC')
            ->value('CODIGO_DOC');
        $siguienteCodDoc = 'DOC001';
        if ($ultimoCod) {
            $num = (int) substr($ultimoCod, 3);
            $siguienteCodDoc = 'DOC' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
        }

        return view('admin.usuarios', compact('usuarios', 'docentes', 'siguienteCodDoc'));
    }

    public function directores(Request $request)
    {
        $cursos = DB::table('ASIGNACION_PCM')
            ->distinct()
            ->pluck('CURSO')
            ->sort(function ($a, $b) {
                $orden = fn($c) => match(true) {
                    $c === 'J'  => [-2, ''],
                    $c === 'T'  => [-1, ''],
                    default     => [(int) $c, ltrim($c, '0123456789')],
                };
                [$na, $la] = $orden($a);
                [$nb, $lb] = $orden($b);
                return $na !== $nb ? $na - $nb : strcmp($la, $lb);
            })
            ->values();

        $directores = DB::table('CODIGOS_DOC')
            ->whereNotNull('DIR_GRUPO')
            ->pluck('CODIGO_DOC', 'DIR_GRUPO');

        $docentes = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        $profile      = auth()->user()->PROFILE;
        $puedeEditar  = in_array($profile, ['SuperAd', 'Admin']);

        return view('admin.directores', compact('cursos', 'directores', 'docentes', 'puedeEditar'));
    }

    public function asignaciones(Request $request)
    {
        $docentesConAsig = DB::table('ASIGNACION_PCM as a')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_DOC', '=', 'd.CODIGO_DOC')
            ->select('a.CODIGO_DOC', DB::raw('COALESCE(d.NOMBRE_DOC, a.CODIGO_DOC) as NOMBRE_DOC'),
                     DB::raw('COUNT(*) as total_asig'))
            ->groupBy('a.CODIGO_DOC', 'd.NOMBRE_DOC')
            ->orderBy('d.NOMBRE_DOC')
            ->get();

        $docentesActivos = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        $verAsigDoc     = $request->input('ver_asig');
        $asigIndividual = collect();
        if ($verAsigDoc) {
            $asigIndividual = DB::table('ASIGNACION_PCM as a')
                ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
                ->where('a.CODIGO_DOC', $verAsigDoc)
                ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT')
                ->orderBy('m.NOMBRE_MAT')
                ->orderBy('a.CURSO')
                ->get()
                ->map(function ($a) {
                    $cursoBase       = explode('-', $a->CURSO)[0];
                    $codMatHorario   = in_array($a->CODIGO_MAT, [25, 26]) ? 70 : $a->CODIGO_MAT;
                    $slots           = DB::table('HORARIOS')
                        ->where('CURSO', $cursoBase)
                        ->where('CODIGO_MAT', $codMatHorario)
                        ->where('CODIGO_MAT', '!=', 0)
                        ->count();
                    $a->tiene_horario = $slots > 0;
                    $a->slots         = $slots;
                    return $a;
                });
        }

        return view('admin.asignaciones', compact(
            'docentesConAsig', 'docentesActivos', 'asigIndividual', 'verAsigDoc'
        ));
    }


    public function verHorarioAsignacion(Request $request)
    {
        $docente    = $request->input('docente');
        $codigoMat  = (int) $request->input('codigo_mat');
        $curso      = $request->input('curso');

        abort_if(!$docente || !$codigoMat || !$curso, 404);

        $cursoBase       = explode('-', $curso)[0];
        $codMatHorario   = in_array($codigoMat, [25, 26]) ? 70 : $codigoMat;

        $nombreDocente = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $docente)->value('NOMBRE_DOC') ?? $docente;
        $nombreMat     = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->value('NOMBRE_MAT') ?? '—';

        // Estado actual completo del curso en HORARIOS
        $horariosCurso = [];
        DB::table('HORARIOS')->where('CURSO', $cursoBase)
            ->get(['DIA', 'HORA', 'CODIGO_MAT'])
            ->each(fn($f) => $horariosCurso[$f->DIA][$f->HORA] = $f->CODIGO_MAT);

        $materiasNombres = DB::table('CODIGOSMAT')->pluck('NOMBRE_MAT', 'CODIGO_MAT')->toArray();
        $dias  = \App\Models\Horario::$dias;
        $horas = \App\Models\Horario::$horas;

        return view('admin.horario_asignacion', compact(
            'docente', 'nombreDocente', 'codigoMat', 'codMatHorario',
            'curso', 'cursoBase', 'nombreMat',
            'horariosCurso', 'materiasNombres', 'dias', 'horas'
        ));
    }

    public function asignarSlot(Request $request)
    {
        $curso           = $request->input('curso');       // curso base
        $dia             = (int) $request->input('dia');
        $hora            = (int) $request->input('hora');
        $codMatHorario   = (int) $request->input('codigo_mat_horario');

        // Solo se permite insertar en slots libres (0 o inexistente)
        $actual = DB::table('HORARIOS')
            ->where('CURSO', $curso)->where('DIA', $dia)->where('HORA', $hora)
            ->value('CODIGO_MAT');

        if ($actual !== null && (int)$actual !== 0) {
            return response()->json(['ok' => false, 'error' => 'El slot ya tiene una materia asignada.'], 422);
        }

        DB::table('HORARIOS')->updateOrInsert(
            ['CURSO' => $curso, 'DIA' => $dia, 'HORA' => $hora],
            ['CODIGO_MAT' => $codMatHorario]
        );

        $estado = [];
        DB::table('HORARIOS')->where('CURSO', $curso)
            ->get(['DIA', 'HORA', 'CODIGO_MAT'])
            ->each(fn($f) => $estado[$f->DIA][$f->HORA] = $f->CODIGO_MAT);

        return response()->json(['ok' => true, 'estado' => $estado]);
    }

    public function storeUsuario(Request $request)
    {
        $request->validate([
            'USER'     => 'required|max:25',
            'PASSWORD' => 'required|min:4|max:250',
            'PROFILE'  => 'required|max:10',
        ], [
            'USER.required'     => 'El usuario es obligatorio.',
            'PASSWORD.required' => 'La contraseña es obligatoria.',
            'PROFILE.required'  => 'El perfil es obligatorio.',
        ]);

        if (DB::table('PRINUSERS')->where('USER', $request->USER)->exists()) {
            return back()->withErrors(['store' => 'Ya existe un usuario con ese nombre.'])->withInput();
        }

        DB::table('PRINUSERS')->insert([
            'USER'     => $request->USER,
            'PASSWORD' => Hash::make($request->PASSWORD),
            'PROFILE'  => strtoupper(trim($request->PROFILE)),
        ]);

        return back()->with('success_usuario', 'Usuario creado correctamente.');
    }

    public function destroyUsuario($user)
    {
        if ($user === auth()->user()->USER) {
            return back()->withErrors(['delete' => 'No puedes eliminar tu propio usuario.']);
        }

        DB::table('PRINUSERS')->where('USER', $user)->delete();

        return back()->with('success_usuario', "Usuario «{$user}» eliminado.");
    }

    public function storeDocente(Request $request)
    {
        $request->validate([
            'CODIGO_DOC' => 'required|max:10',
            'NOMBRE_DOC' => 'required|max:150',
            'TIPO'       => 'required|in:DOCENTE,ADMINISTRATIVO',
        ]);

        $codigo = strtoupper(trim($request->CODIGO_DOC));

        if (DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigo)->exists()) {
            return back()->withErrors(['docente_store' => "Ya existe un docente con el código «{$codigo}»."]);
        }

        $ultimo = DB::table('CODIGOS_DOC')->max('ID_DOCENTE') ?? 0;

        DB::table('CODIGOS_DOC')->insert([
            'ID_DOCENTE'  => $ultimo + 1,
            'CODIGO_DOC'  => $codigo,
            'NOMBRE_DOC'  => trim($request->NOMBRE_DOC),
            'TIPO'        => $request->TIPO,
            'ESTADO'      => 'ACTIVO',
            'FECHA_CREACION' => now(),
        ]);

        return back()->with('success_docente', "Docente «{$request->NOMBRE_DOC}» creado con código {$codigo}.");
    }

    public function toggleDocente($codigo)
    {
        $docente = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigo)->first();

        if (!$docente) {
            return back()->withErrors(['docente' => 'Docente no encontrado.']);
        }

        $nuevoEstado = $docente->ESTADO === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigo)->update(['ESTADO' => $nuevoEstado]);

        return back()->with('success_docente', "Docente «{$docente->NOMBRE_DOC}» marcado como {$nuevoEstado}.");
    }

    public function setEstadoDocente(Request $request, $codigo)
    {
        $docente = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigo)->first();
        if (!$docente) return back()->withErrors(['docente' => 'Docente no encontrado.']);

        $estado = $request->input('estado');
        if (!in_array($estado, ['ACTIVO', 'INCAPACIDAD', 'INACTIVO'])) {
            return back()->withErrors(['docente' => 'Estado inválido.']);
        }

        DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigo)->update(['ESTADO' => $estado]);

        return back()->with('success_docente', "Docente «{$docente->NOMBRE_DOC}» marcado como {$estado}.");
    }

    public function moverUnaAsignacion(Request $request)
    {
        $origen   = $request->input('origen');
        $destino  = $request->input('destino');
        $mat      = $request->input('CODIGO_MAT');
        $curso    = $request->input('CURSO');

        if (!$origen || !$destino || !$mat || !$curso) {
            return back()->withErrors(['mover_una' => 'Faltan datos para mover la asignación.']);
        }

        if ($origen === $destino) {
            return back()->withErrors(['mover_una' => 'El docente destino debe ser diferente al origen.']);
        }

        DB::table('ASIGNACION_PCM')
            ->where('CODIGO_DOC', $origen)
            ->where('CODIGO_MAT', $mat)
            ->where('CURSO', $curso)
            ->update(['CODIGO_DOC' => $destino]);

        return redirect()
            ->route('admin.asignaciones', ['ver_asig' => $origen])
            ->with('success_mover_una', "Asignación movida correctamente a «{$destino}».");
    }

    public function asignarDirGrupo(Request $request)
    {
        $curso   = trim($request->input('curso'));
        $docente = trim($request->input('docente'));

        // Quitar cualquier director previo de ese curso
        DB::table('CODIGOS_DOC')->where('DIR_GRUPO', $curso)->update(['DIR_GRUPO' => null]);

        // Asignar si se eligió un docente
        if ($docente) {
            DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $docente)->update(['DIR_GRUPO' => $curso]);
            return back()->with('success_dir_grupo', "Director del curso {$curso} asignado correctamente.");
        }

        return back()->with('success_dir_grupo', "Director del curso {$curso} removido.");
    }

    public function moverAsignaciones(Request $request)
    {
        $request->validate([
            'origen'  => 'required',
            'destino' => 'required|different:origen',
        ], [
            'destino.different' => 'El docente destino debe ser diferente al origen.',
        ]);

        $origen  = $request->origen;
        $destino = $request->destino;

        $total = DB::table('ASIGNACION_PCM')->where('CODIGO_DOC', $origen)->count();

        if ($total === 0) {
            return back()->withErrors(['mover' => 'El docente origen no tiene asignaciones.']);
        }

        DB::table('ASIGNACION_PCM')->where('CODIGO_DOC', $origen)->update(['CODIGO_DOC' => $destino]);

        return back()->with('success_mover', "{$total} asignación(es) movidas de «{$origen}» a «{$destino}».");
    }
}
