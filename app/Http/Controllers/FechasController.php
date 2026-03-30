<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FechasController extends Controller
{
    // Grupos de códigos con sus etiquetas
    public static function grupos(): array
    {
        return [
            'P' => ['label' => 'Ingreso de Notas por Docentes',  'icon' => '📝'],
            'D' => ['label' => 'Consulta de Derroteros',          'icon' => '🗺️'],
            'S' => ['label' => 'Consulta de Salvavidas',          'icon' => '🏊'],
            'B' => ['label' => 'Consulta de Boletines',           'icon' => '📋'],
            'F' => ['label' => 'Consulta de Notas Finales',       'icon' => '🎓'],
        ];
    }

    // Verifica si un código está activo ahora mismo
    public static function estaActivo(string $codigo): bool
    {
        $now = now();
        return DB::table('FECHAS')
            ->where('CODIGO_FECHA', $codigo)
            ->where('INICIO', '<=', $now)
            ->where('FIN',    '>=', $now)
            ->exists();
    }

    // Retorna todos los códigos activos ahora mismo
    public static function codigosActivos(): array
    {
        $now = now();
        return DB::table('FECHAS')
            ->where('INICIO', '<=', $now)
            ->where('FIN',    '>=', $now)
            ->pluck('CODIGO_FECHA')
            ->toArray();
    }

    // Verifica si algún código del prefijo está activo (ej: 'D' → D1, D2, D3, D4)
    public static function prefixActivo(string $prefix): bool
    {
        $now = now();
        return DB::table('FECHAS')
            ->where('CODIGO_FECHA', 'like', $prefix . '%')
            ->where('INICIO', '<=', $now)
            ->where('FIN',    '>=', $now)
            ->exists();
    }

    public function index()
    {
        $fechas = DB::table('FECHAS')
            ->orderBy('CODIGO_FECHA')
            ->get()
            ->keyBy('CODIGO_FECHA');

        $grupos = self::grupos();

        return view('admin.fechas', compact('fechas', 'grupos'));
    }

    public function upsert(Request $request)
    {
        $request->validate([
            'CODIGO_FECHA' => 'required|max:10',
            'INICIO'       => 'required|date',
            'FIN'          => 'required|date|after_or_equal:INICIO',
        ], [
            'FIN.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
        ]);

        $codigo = strtoupper(trim($request->CODIGO_FECHA));
        $inicio = $request->INICIO;
        $fin    = $request->FIN;

        $existe = DB::table('FECHAS')->where('CODIGO_FECHA', $codigo)->exists();

        if ($existe) {
            DB::table('FECHAS')->where('CODIGO_FECHA', $codigo)
                ->update(['INICIO' => $inicio, 'FIN' => $fin]);
        } else {
            DB::table('FECHAS')->insert([
                'CODIGO_FECHA' => $codigo,
                'INICIO'       => $inicio,
                'FIN'          => $fin,
            ]);
        }

        return back()->with('success_fechas', "Fecha «{$codigo}» guardada correctamente.");
    }

    public function destroy(string $codigo)
    {
        DB::table('FECHAS')->where('CODIGO_FECHA', $codigo)->delete();
        return back()->with('success_fechas', "Fecha «{$codigo}» eliminada.");
    }
}
