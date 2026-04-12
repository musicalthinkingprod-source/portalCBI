<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExencionCarteraController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('q', '');

        // Exenciones activas (sin vencer)
        $exenciones = DB::table('exenciones_cartera as e')
            ->join('ESTUDIANTES as es', 'es.CODIGO', '=', 'e.codigo_alumno')
            ->where(function ($q) use ($busqueda) {
                if ($busqueda) {
                    $q->where('es.CODIGO', 'like', "%{$busqueda}%")
                      ->orWhere('es.NOMBRE1', 'like', "%{$busqueda}%")
                      ->orWhere('es.APELLIDO1', 'like', "%{$busqueda}%");
                }
            })
            ->select(
                'e.id', 'e.codigo_alumno', 'e.motivo', 'e.vence_en', 'e.creado_por', 'e.created_at',
                'es.NOMBRE1', 'es.NOMBRE2', 'es.APELLIDO1', 'es.APELLIDO2', 'es.CURSO'
            )
            ->orderByRaw("CASE WHEN e.vence_en IS NULL OR e.vence_en >= CURDATE() THEN 0 ELSE 1 END")
            ->orderBy('e.created_at', 'desc')
            ->get()
            ->map(function ($row) {
                $row->vencida = $row->vence_en && $row->vence_en < today()->toDateString();
                return $row;
            });

        return view('admin.exenciones-cartera', compact('exenciones', 'busqueda'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_alumno'     => 'required|integer',
            'motivo'            => 'nullable|string|max:200',
            'vence_en'          => 'nullable|date|after:today',
            'nota_seguimiento'  => 'nullable|string|max:2000',
            'tipo_seguimiento'  => 'nullable|string|max:30',
        ], [
            'vence_en.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ]);

        $existe = DB::table('ESTUDIANTES')->where('CODIGO', $request->codigo_alumno)->exists();
        if (!$existe) {
            return back()->withErrors(['codigo_alumno' => 'No existe ningún estudiante con ese código.'])->withInput();
        }

        // Si ya tiene exención activa, no crear duplicado
        $yaActiva = DB::table('exenciones_cartera')
            ->where('codigo_alumno', $request->codigo_alumno)
            ->where(function ($q) {
                $q->whereNull('vence_en')->orWhere('vence_en', '>=', today());
            })
            ->exists();

        if ($yaActiva) {
            return back()->withErrors(['codigo_alumno' => 'Este estudiante ya tiene una exención activa.'])->withInput();
        }

        DB::table('exenciones_cartera')->insert([
            'codigo_alumno' => $request->codigo_alumno,
            'motivo'        => $request->motivo ?: null,
            'vence_en'      => $request->vence_en ?: null,
            'creado_por'    => auth()->user()->USER,
            'created_at'    => now(),
        ]);

        // Registrar en seguimiento de cartera si se indicó nota
        if ($request->filled('nota_seguimiento')) {
            $motivo  = $request->motivo ? ' — ' . $request->motivo : '';
            $vence   = $request->vence_en ? ' (hasta ' . \Carbon\Carbon::parse($request->vence_en)->format('d/m/Y') . ')' : ' (sin vencimiento)';
            $prefijo = "Exención portal padres{$vence}{$motivo}: ";

            DB::table('seguimiento_cartera')->insert([
                'codigo_alumno' => $request->codigo_alumno,
                'tipo'          => $request->tipo_seguimiento ?: 'Nota',
                'nota'          => $prefijo . $request->nota_seguimiento,
                'usuario'       => auth()->user()->USER,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        return back()->with('success', 'Exención creada' . ($request->filled('nota_seguimiento') ? ' y nota registrada en seguimiento.' : ' correctamente.'));
    }

    public function destroy(int $id)
    {
        DB::table('exenciones_cartera')->where('id', $id)->delete();
        return back()->with('success', 'Exención revocada.');
    }

    /**
     * Comprueba si un estudiante tiene exención de cartera activa (sin vencer).
     * Usado desde PadresController.
     */
    public static function tieneExencion(int $codigoAlumno): bool
    {
        return DB::table('exenciones_cartera')
            ->where('codigo_alumno', $codigoAlumno)
            ->where(function ($q) {
                $q->whereNull('vence_en')->orWhere('vence_en', '>=', today());
            })
            ->exists();
    }
}
