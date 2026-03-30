<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = DB::table('PRINUSERS')->orderBy('PROFILE')->orderBy('USER')->get();

        $docentes = DB::table('CODIGOS_DOC')
            ->orderBy('ESTADO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        // Docentes con asignaciones (para mover en bloque y en detalle)
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

        // Siguiente código DOC disponible
        $ultimoCod = DB::table('CODIGOS_DOC')
            ->where('CODIGO_DOC', 'like', 'DOC%')
            ->orderByRaw('CAST(SUBSTRING(CODIGO_DOC, 4) AS UNSIGNED) DESC')
            ->value('CODIGO_DOC');
        $siguienteCodDoc = 'DOC001';
        if ($ultimoCod) {
            $num = (int) substr($ultimoCod, 3);
            $siguienteCodDoc = 'DOC' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
        }

        // Asignaciones individuales del docente seleccionado
        $verAsigDoc     = $request->input('ver_asig');
        $asigIndividual = collect();
        if ($verAsigDoc) {
            $asigIndividual = DB::table('ASIGNACION_PCM as a')
                ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
                ->where('a.CODIGO_DOC', $verAsigDoc)
                ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT')
                ->orderBy('m.NOMBRE_MAT')
                ->orderBy('a.CURSO')
                ->get();
        }

        return view('admin.usuarios', compact(
            'usuarios', 'docentes', 'docentesConAsig', 'docentesActivos',
            'asigIndividual', 'verAsigDoc', 'siguienteCodDoc'
        ));
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
            ->route('admin.usuarios', ['ver_asig' => $origen])
            ->with('success_mover_una', "Asignación movida correctamente a «{$destino}».");
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
