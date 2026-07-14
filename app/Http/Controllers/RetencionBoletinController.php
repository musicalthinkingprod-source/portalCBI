<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetencionBoletinController extends Controller
{
    /**
     * Catálogo de tipos de retención.
     *  - label   : nombre del área que retiene (se muestra al padre)
     *  - correo  : contacto para solicitar la activación
     *  - contacto: responsable/dependencia
     *  - ret_col : columna booleana en ESTUDIANTES que se mantiene en sincronía
     *  - perfiles: perfiles autorizados a activar/levantar esta retención
     */
    public const TIPOS = [
        'ACAD' => [
            'label'    => 'Coordinación Académica',
            'correo'   => 'academic_coordination@cbi.edu.co',
            'contacto' => 'Coordinación Académica',
            'ret_col'  => 'RET_ACAD',
            'perfiles' => ['SuperAd', 'COR001'],
        ],
        'CONV' => [
            'label'    => 'Coordinación de Convivencia',
            'correo'   => 'coordination@cbi.edu.co',
            'contacto' => 'Coordinación de Convivencia',
            'ret_col'  => 'RET_CONV',
            'perfiles' => ['SuperAd', 'COR002'],
        ],
        'RECT' => [
            'label'    => 'Rectoría',
            'correo'   => 'administration@cbi.edu.co',
            'contacto' => 'Rectoría',
            'ret_col'  => 'RET_RECT',
            'perfiles' => ['SuperAd'],
        ],
        'CART' => [
            'label'    => 'Tesorería (Cartera)',
            'correo'   => 'tesoreria@cbi.edu.co',
            'contacto' => 'Tesorería',
            'ret_col'  => 'RET_CART',
            'perfiles' => ['SuperAd'],
        ],
    ];

    /** ¿El perfil dado puede gestionar retenciones de este tipo? */
    public static function puedeGestionar(string $tipo, ?string $profile = null): bool
    {
        $profile ??= auth()->user()?->PROFILE;
        $conf = self::TIPOS[$tipo] ?? null;
        return $conf !== null && in_array($profile, $conf['perfiles'], true);
    }

    /** Tipos que el usuario actual puede gestionar. */
    public static function tiposGestionables(?string $profile = null): array
    {
        $profile ??= auth()->user()?->PROFILE;
        return array_keys(array_filter(
            self::TIPOS,
            fn($conf) => in_array($profile, $conf['perfiles'], true)
        ));
    }

    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $retenciones = DB::table('retenciones_boletin as r')
            ->join('ESTUDIANTES as es', 'es.CODIGO', '=', 'r.codigo_alumno')
            ->when($busqueda !== '', function ($q) use ($busqueda) {
                $q->where(function ($w) use ($busqueda) {
                    $w->where('es.CODIGO', 'like', "%{$busqueda}%")
                      ->orWhere('es.NOMBRE1', 'like', "%{$busqueda}%")
                      ->orWhere('es.APELLIDO1', 'like', "%{$busqueda}%")
                      ->orWhere('es.APELLIDO2', 'like', "%{$busqueda}%");
                });
            })
            ->select(
                'r.id', 'r.codigo_alumno', 'r.tipo', 'r.motivo',
                'r.retenido_por', 'r.created_at',
                'es.NOMBRE1', 'es.NOMBRE2', 'es.APELLIDO1', 'es.APELLIDO2', 'es.CURSO'
            )
            ->orderBy('r.created_at', 'desc')
            ->get();

        $gestionables = self::tiposGestionables();
        $tipos        = self::TIPOS;

        return view('admin.retencion-boletines', compact('retenciones', 'busqueda', 'gestionables', 'tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_alumno' => 'required|integer',
            'tipo'          => 'required|string',
            'motivo'        => 'nullable|string|max:200',
        ]);

        $tipo = strtoupper($request->tipo);

        if (!isset(self::TIPOS[$tipo])) {
            return back()->withErrors(['tipo' => 'Tipo de retención no válido.'])->withInput();
        }
        if (!self::puedeGestionar($tipo)) {
            return back()->withErrors(['tipo' => 'No tienes permiso para aplicar esta retención.'])->withInput();
        }

        $existe = DB::table('ESTUDIANTES')->where('CODIGO', $request->codigo_alumno)->exists();
        if (!$existe) {
            return back()->withErrors(['codigo_alumno' => 'No existe ningún estudiante con ese código.'])->withInput();
        }

        $yaActiva = DB::table('retenciones_boletin')
            ->where('codigo_alumno', $request->codigo_alumno)
            ->where('tipo', $tipo)
            ->exists();

        if ($yaActiva) {
            return back()->withErrors(['codigo_alumno' => 'Este estudiante ya tiene una retención activa de ' . self::TIPOS[$tipo]['label'] . '.'])->withInput();
        }

        DB::table('retenciones_boletin')->insert([
            'codigo_alumno'   => $request->codigo_alumno,
            'tipo'            => $tipo,
            'motivo'          => $request->motivo ?: null,
            'retenido_por'    => auth()->user()->USER,
            'retenido_perfil' => auth()->user()->PROFILE,
            'created_at'      => now(),
        ]);

        // Mantener el booleano RET_* en sincronía (filtros de búsqueda de estudiantes).
        DB::table('ESTUDIANTES')->where('CODIGO', $request->codigo_alumno)
            ->update([self::TIPOS[$tipo]['ret_col'] => true]);

        return back()->with('success', 'Retención de ' . self::TIPOS[$tipo]['label'] . ' aplicada.');
    }

    public function destroy(int $id)
    {
        $ret = DB::table('retenciones_boletin')->where('id', $id)->first();
        if (!$ret) {
            return back()->withErrors(['id' => 'La retención ya no existe.']);
        }
        if (!self::puedeGestionar($ret->tipo)) {
            return back()->withErrors(['id' => 'No tienes permiso para levantar esta retención.']);
        }

        DB::table('retenciones_boletin')->where('id', $id)->delete();

        // Apagar el booleano RET_* correspondiente.
        if (isset(self::TIPOS[$ret->tipo])) {
            DB::table('ESTUDIANTES')->where('CODIGO', $ret->codigo_alumno)
                ->update([self::TIPOS[$ret->tipo]['ret_col'] => false]);
        }

        return back()->with('success', 'Retención levantada. El acudiente ya puede consultar boletines y promedios.');
    }

    /**
     * Retenciones activas de un estudiante, con datos de contacto para el padre.
     * Devuelve una colección de arrays: tipo, label, correo, contacto, motivo, retenido_por, created_at.
     */
    public static function retencionesActivas(int $codigo)
    {
        return DB::table('retenciones_boletin')
            ->where('codigo_alumno', $codigo)
            ->orderBy('created_at')
            ->get()
            ->map(function ($r) {
                $conf = self::TIPOS[$r->tipo] ?? null;
                return [
                    'tipo'         => $r->tipo,
                    'label'        => $conf['label']    ?? $r->tipo,
                    'correo'       => $conf['correo']   ?? null,
                    'contacto'     => $conf['contacto'] ?? null,
                    'motivo'       => $r->motivo,
                    'retenido_por' => $r->retenido_por,
                    'created_at'   => $r->created_at,
                ];
            });
    }

    /** ¿El estudiante tiene alguna retención activa? */
    public static function estaRetenido(int $codigo): bool
    {
        return DB::table('retenciones_boletin')->where('codigo_alumno', $codigo)->exists();
    }

    /**
     * Mensaje de aviso para el acudiente cuando intenta consultar un módulo retenido
     * (boletines, promedios, recuperaciones o salvavidas). Indica qué área retiene y
     * su correo de contacto. Devuelve null si no hay retenciones activas.
     */
    public static function mensajeAviso(int $codigo): ?string
    {
        $retenciones = self::retencionesActivas($codigo);
        if ($retenciones->isEmpty()) return null;

        $partes = $retenciones->map(function ($r) {
            $txt = $r['label'];
            if ($r['correo']) $txt .= ' (' . $r['correo'] . ')';
            return $txt;
        })->all();

        return 'Esta consulta está retenida por ' . implode(' y ', $partes)
             . '. Comunícate con el área correspondiente para su activación.';
    }
}
