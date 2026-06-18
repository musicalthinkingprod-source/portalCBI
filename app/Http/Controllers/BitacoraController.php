<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BitacoraController extends Controller
{
    /** ¿Es un perfil de docente? */
    private function esDocente(string $profile): bool
    {
        return str_starts_with($profile, 'DOC');
    }

    /** Query base de las categorías que un perfil puede usar para registrar. */
    private function categoriasPermitidasQuery(string $profile)
    {
        $q = DB::table('bitacora_categorias')->where('activo', 1);

        return match (true) {
            $profile === 'SuperAd'        => $q,
            $profile === 'COR001'         => $q->whereIn('ambito', ['academico', 'general']),
            $profile === 'COR002'         => $q->whereIn('ambito', ['convivencia', 'general']),
            $this->esDocente($profile)    => $q->where('docentes', 1),
            default                       => $q->whereRaw('1 = 0'),
        };
    }

    /** ¿Puede este perfil usar esta categoría? (no exige que esté activa, para poder editar entradas viejas) */
    private function puedeUsarCategoria(string $profile, $categoria): bool
    {
        if (!$categoria) return false;

        return match (true) {
            $profile === 'SuperAd'     => true,
            $profile === 'COR001'      => in_array($categoria->ambito, ['academico', 'general'], true),
            $profile === 'COR002'      => in_array($categoria->ambito, ['convivencia', 'general'], true),
            $this->esDocente($profile) => (bool) $categoria->docentes,
            default                    => false,
        };
    }

    /** ¿Puede este perfil editar/eliminar esta entrada? Los docentes solo las suyas. */
    private function puedeEditarEntrada(string $profile, $entrada): bool
    {
        $categoria = DB::table('bitacora_categorias')->where('id', $entrada->categoria_id)->first();
        if (!$this->puedeUsarCategoria($profile, $categoria)) return false;
        if ($this->esDocente($profile) && $entrada->registrado_por !== auth()->user()->USER) return false;
        return true;
    }

    /** Materias que dicta un docente (su carga en ASIGNACION_PCM), sin "Atención a Padres". */
    private function materiasDocente(string $profile)
    {
        return DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->where('a.CODIGO_EMP', $profile)
            ->where('a.CODIGO_MAT', '!=', 200)
            ->distinct()
            ->orderBy('m.NOMBRE_MAT')
            ->get(['a.CODIGO_MAT as codigo_mat', 'm.NOMBRE_MAT as nombre_mat']);
    }

    /** Nombre del docente según su código de empleado (= su PROFILE). */
    private function nombreDocente(string $profile): ?string
    {
        return DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $profile)->value('NOMBRE_DOC');
    }

    /** Cursos reales (los de los estudiantes matriculados), sin grupos de listados especiales. */
    private function cursosReales()
    {
        return $this->ordenarCursos(
            DB::table('ESTUDIANTES')
                ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
                ->whereNotNull('CURSO')->where('CURSO', '!=', '')
                ->distinct()->pluck('CURSO')
        );
    }

    /** Ordena cursos: J, T primero; luego por grado numérico y sección. */
    private function ordenarCursos($cursos)
    {
        return $cursos->sort(function ($a, $b) {
            $orden = fn($c) => match (true) {
                $c === 'J' => [-2, ''],
                $c === 'T' => [-1, ''],
                default    => [(int) $c, ltrim($c, '0123456789')],
            };
            [$na, $la] = $orden($a);
            [$nb, $lb] = $orden($b);
            return $na !== $nb ? $na - $nb : strcmp($la, $lb);
        })->values();
    }

    public function index(Request $request)
    {
        $profile   = auth()->user()->PROFILE;
        $esDocente = $this->esDocente($profile);

        // Cursos para el selector
        $cursos = $this->cursosReales();

        // Categorías que este perfil puede usar (para el formulario)
        $categorias = $this->categoriasPermitidasQuery($profile)->orderBy('nombre')->get();

        // Materias del docente (para el selector de "registro de aula")
        $materias = $esDocente ? $this->materiasDocente($profile) : collect();

        // Plantillas activas (el JS las filtra por categoría elegida)
        $plantillas = DB::table('bitacora_plantillas')
            ->where('activo', 1)
            ->orderBy('texto')
            ->get();

        // Estudiantes del curso seleccionado (para el selector del formulario)
        $cursoForm   = $request->input('curso_form');
        $estudiantes = collect();
        $historialPorEstudiante = [];
        if ($cursoForm) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoForm)
                ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get(['CODIGO', 'NOMBRE1', 'NOMBRE2', 'APELLIDO1', 'APELLIDO2']);

            // Historial de los estudiantes del curso para mostrarlo al seleccionar uno
            if ($estudiantes->isNotEmpty()) {
                $histQuery = DB::table('bitacora_entradas as b')
                    ->join('bitacora_categorias as c', 'c.id', '=', 'b.categoria_id')
                    ->whereIn('b.codigo_alumno', $estudiantes->pluck('CODIGO')->all())
                    ->orderByDesc('b.fecha')->orderByDesc('b.id');

                // Los docentes solo ven sus propios registros
                if ($esDocente) $histQuery->where('b.registrado_por', auth()->user()->USER);

                $hist = $histQuery
                    ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'b.codigo_mat')
                    ->get(['b.codigo_alumno', 'b.fecha', 'b.observacion', 'c.nombre as categoria', 'c.color', 'm.NOMBRE_MAT as materia']);

                foreach ($hist as $h) {
                    $historialPorEstudiante[$h->codigo_alumno][] = [
                        'fecha'       => \Carbon\Carbon::parse($h->fecha)->locale('es')->isoFormat('D MMM YYYY'),
                        'categoria'   => $h->categoria,
                        'color'       => $h->color,
                        'materia'     => $h->materia,
                        'observacion' => $h->observacion,
                    ];
                }
            }
        }

        // ── Entradas registradas (con filtros) ──────────────────────────────
        $fCurso     = $request->input('f_curso');
        $fCodigo    = $request->input('f_codigo');
        $fCategoria = $request->input('f_categoria');

        $query = DB::table('bitacora_entradas as b')
            ->join('bitacora_categorias as c', 'c.id', '=', 'b.categoria_id')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'b.codigo_alumno')
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'b.codigo_mat')
            ->select(
                'b.*',
                'c.nombre as categoria', 'c.color as categoria_color', 'c.ambito',
                'm.NOMBRE_MAT as materia',
                'e.CURSO',
                DB::raw("TRIM(CONCAT_WS(' ', e.NOMBRE1, e.NOMBRE2, e.APELLIDO1, e.APELLIDO2)) as nombre_alumno")
            )
            ->orderByDesc('b.fecha')
            ->orderByDesc('b.id');

        if ($fCurso)     $query->where('e.CURSO', $fCurso);
        if ($fCodigo)    $query->where('b.codigo_alumno', (int) $fCodigo);
        if ($fCategoria) $query->where('b.categoria_id', (int) $fCategoria);

        // Los docentes solo ven sus propios registros
        if ($esDocente) $query->where('b.registrado_por', auth()->user()->USER);

        $entradas = $query->limit(300)->get();

        // Catálogo de categorías para el filtro: docentes solo las suyas; el resto todas
        $todasCategorias = $esDocente
            ? $this->categoriasPermitidasQuery($profile)->orderBy('nombre')->get()
            : DB::table('bitacora_categorias')->orderBy('nombre')->get();

        return view('bitacora.index', compact(
            'cursos', 'categorias', 'plantillas', 'estudiantes', 'cursoForm',
            'entradas', 'todasCategorias', 'fCurso', 'fCodigo', 'fCategoria',
            'historialPorEstudiante', 'esDocente', 'materias'
        ));
    }

    public function store(Request $request)
    {
        $profile = auth()->user()->PROFILE;

        $data = $request->validate([
            'codigo_alumno' => 'required|integer',
            'categoria_id'  => 'required|integer',
            'fecha'         => 'required|date',
            'observacion'   => 'required|string|max:8000',
        ]);

        $categoria = DB::table('bitacora_categorias')->where('id', $data['categoria_id'])->first();
        if (!$this->puedeUsarCategoria($profile, $categoria)) {
            return back()->with('error', 'No tienes permiso para registrar observaciones de esa categoría.')->withInput();
        }

        // El estudiante debe existir
        $existe = DB::table('ESTUDIANTES')->where('CODIGO', $data['codigo_alumno'])->exists();
        if (!$existe) {
            return back()->with('error', 'El estudiante indicado no existe.')->withInput();
        }

        $codigo = (int) $data['codigo_alumno'];
        $fecha  = $data['fecha'];
        $catId  = (int) $data['categoria_id'];
        $texto  = mb_substr(trim($data['observacion']), 0, 8000);
        $anio   = (int) date('Y', strtotime($fecha));

        // Registro de aula (docente): debe escoger una materia entre las que dicta
        $codigoMat = null;
        $registradoNombre = null;
        if ($this->esDocente($profile)) {
            $codigoMat   = (int) $request->input('codigo_mat');
            $materiasIds = $this->materiasDocente($profile)->pluck('codigo_mat')->map(fn($x) => (int) $x)->all();
            if (!in_array($codigoMat, $materiasIds, true)) {
                return back()->with('error', 'Debes seleccionar una materia válida entre las que dictas.')->withInput();
            }
            $registradoNombre = $this->nombreDocente($profile);
        }

        // Categorías "únicas" (ej. Consejo Académico): no se duplican; reemplazan por (estudiante, fecha, categoría)
        if ($categoria->unica) {
            $existente = DB::table('bitacora_entradas')
                ->where(['codigo_alumno' => $codigo, 'fecha' => $fecha, 'categoria_id' => $catId])
                ->first();
            if ($existente) {
                DB::table('bitacora_entradas')->where('id', $existente->id)->update([
                    'observacion'    => $texto,
                    'anio'           => $anio,
                    'registrado_por' => auth()->user()->USER,
                ]);
                return redirect()->route('bitacora.index', ['f_codigo' => $codigo])
                    ->with('ok', 'Observación actualizada (registro único: reemplaza el anterior de esa fecha).');
            }
        }

        DB::table('bitacora_entradas')->insert([
            'codigo_alumno'     => $codigo,
            'categoria_id'      => $catId,
            'codigo_mat'        => $codigoMat,
            'fecha'             => $fecha,
            'anio'              => $anio,
            'observacion'       => $texto,
            'registrado_por'    => auth()->user()->USER,
            'registrado_nombre' => $registradoNombre,
            'created_at'        => now(),
        ]);

        return redirect()->route('bitacora.index', ['f_codigo' => $codigo])
            ->with('ok', 'Observación registrada correctamente.');
    }

    // ── Carga masiva por curso ──────────────────────────────────────────────

    public function masivaForm(Request $request)
    {
        $profile = auth()->user()->PROFILE;

        $cursos = $this->cursosReales();

        $categorias = $this->categoriasPermitidasQuery($profile)->orderBy('nombre')->get();

        $curso       = $request->input('curso');
        $fecha       = $request->input('fecha', now()->toDateString());
        $categoriaId = (int) $request->input('categoria_id');
        $plantillaId = (int) $request->input('plantilla_id');

        // La categoría elegida debe ser usable por este perfil
        $categoria = $categoriaId
            ? DB::table('bitacora_categorias')->where('id', $categoriaId)->first()
            : null;
        $categoriaValida = $this->puedeUsarCategoria($profile, $categoria);

        // Todas las plantillas activas; el filtrado por categoría se hace en vivo (Alpine)
        $plantillas = DB::table('bitacora_plantillas')
            ->where('activo', 1)
            ->orderBy('texto')
            ->get();

        $textoDefault = '';
        if ($plantillaId) {
            $textoDefault = (string) optional($plantillas->firstWhere('id', $plantillaId))->texto;
        }

        $estudiantes = collect();
        $prefill     = [];
        if ($curso && $categoriaValida) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $curso)
                ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get(['CODIGO', 'NOMBRE1', 'NOMBRE2', 'APELLIDO1', 'APELLIDO2']);

            $existentes = DB::table('bitacora_entradas')
                ->where('fecha', $fecha)
                ->where('categoria_id', $categoriaId)
                ->whereIn('codigo_alumno', $estudiantes->pluck('CODIGO')->all())
                ->pluck('observacion', 'codigo_alumno');

            // Prefill: lo ya guardado para (estudiante, fecha, categoría); si no hay, el texto por defecto.
            foreach ($estudiantes as $est) {
                $prefill[$est->CODIGO] = $existentes[$est->CODIGO] ?? $textoDefault;
            }
        }

        return view('bitacora.masiva', compact(
            'cursos', 'categorias', 'plantillas', 'estudiantes', 'prefill',
            'curso', 'fecha', 'categoriaId', 'plantillaId', 'textoDefault', 'categoriaValida'
        ));
    }

    public function masivaGuardar(Request $request)
    {
        $profile = auth()->user()->PROFILE;

        $data = $request->validate([
            'curso'        => 'required|string',
            'categoria_id' => 'required|integer',
            'fecha'        => 'required|date',
            'obs'          => 'array',
        ]);

        $categoria = DB::table('bitacora_categorias')->where('id', $data['categoria_id'])->first();
        if (!$this->puedeUsarCategoria($profile, $categoria)) {
            return back()->with('error', 'No tienes permiso para registrar observaciones de esa categoría.')->withInput();
        }

        $fecha = $data['fecha'];
        $catId = (int) $data['categoria_id'];
        $anio  = (int) date('Y', strtotime($fecha));

        $codigosValidos = DB::table('ESTUDIANTES')
            ->where('CURSO', $data['curso'])
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->pluck('CODIGO')
            ->map(fn($c) => (int) $c)
            ->all();

        $creadas = 0; $actualizadas = 0; $eliminadas = 0;

        foreach ($request->input('obs', []) as $codigo => $texto) {
            $codigo = (int) $codigo;
            if (!in_array($codigo, $codigosValidos, true)) continue;

            $texto = trim((string) $texto);
            $clave = ['codigo_alumno' => $codigo, 'fecha' => $fecha, 'categoria_id' => $catId];

            $existente = DB::table('bitacora_entradas')->where($clave)->first();

            if ($texto === '') {
                // Vacío = quitar la observación de esa fecha/categoría si existía
                if ($existente) { DB::table('bitacora_entradas')->where('id', $existente->id)->delete(); $eliminadas++; }
                continue;
            }

            if ($existente) {
                DB::table('bitacora_entradas')->where('id', $existente->id)->update([
                    'observacion'    => mb_substr($texto, 0, 8000),
                    'anio'           => $anio,
                    'registrado_por' => auth()->user()->USER,
                ]);
                $actualizadas++;
            } else {
                DB::table('bitacora_entradas')->insert($clave + [
                    'observacion'    => mb_substr($texto, 0, 8000),
                    'anio'           => $anio,
                    'registrado_por' => auth()->user()->USER,
                    'created_at'     => now(),
                ]);
                $creadas++;
            }
        }

        return redirect()->route('bitacora.masiva', [
                'curso' => $data['curso'], 'fecha' => $fecha, 'categoria_id' => $catId,
            ])
            ->with('ok', "Carga masiva guardada: {$creadas} nuevas, {$actualizadas} actualizadas, {$eliminadas} eliminadas.");
    }

    public function update(Request $request, int $id)
    {
        $profile = auth()->user()->PROFILE;

        $data = $request->validate([
            'categoria_id' => 'required|integer',
            'fecha'        => 'required|date',
            'observacion'  => 'required|string|max:8000',
        ]);

        $entrada = DB::table('bitacora_entradas')->where('id', $id)->first();
        if (!$entrada) {
            return back()->with('error', 'La observación no existe.');
        }

        // Debe poder editar la entrada actual (docentes solo las suyas)
        if (!$this->puedeEditarEntrada($profile, $entrada)) {
            return back()->with('error', 'No puedes editar esta observación.');
        }

        // Y debe poder usar la categoría destino
        $categoria = DB::table('bitacora_categorias')->where('id', $data['categoria_id'])->first();
        if (!$this->puedeUsarCategoria($profile, $categoria)) {
            return back()->with('error', 'No tienes permiso para usar esa categoría.');
        }

        $cambios = [
            'categoria_id' => $data['categoria_id'],
            'fecha'        => $data['fecha'],
            'anio'         => (int) date('Y', strtotime($data['fecha'])),
            'observacion'  => mb_substr(trim($data['observacion']), 0, 8000),
        ];

        // Registro de aula (docente): puede cambiar la materia entre las que dicta
        if ($this->esDocente($profile)) {
            $codigoMat   = (int) $request->input('codigo_mat');
            $materiasIds = $this->materiasDocente($profile)->pluck('codigo_mat')->map(fn($x) => (int) $x)->all();
            if (!in_array($codigoMat, $materiasIds, true)) {
                return back()->with('error', 'Debes seleccionar una materia válida entre las que dictas.');
            }
            $cambios['codigo_mat'] = $codigoMat;
        }

        DB::table('bitacora_entradas')->where('id', $id)->update($cambios);

        return redirect()->route('bitacora.index', ['f_codigo' => $entrada->codigo_alumno])
            ->with('ok', 'Observación actualizada.');
    }

    public function destroy(int $id)
    {
        $profile = auth()->user()->PROFILE;

        $entrada = DB::table('bitacora_entradas')->where('id', $id)->first();
        if (!$entrada) {
            return back()->with('error', 'La observación no existe.');
        }

        if (!$this->puedeEditarEntrada($profile, $entrada)) {
            return back()->with('error', 'No puedes eliminar esta observación.');
        }

        DB::table('bitacora_entradas')->where('id', $id)->delete();

        return back()->with('ok', 'Observación eliminada.');
    }

    // ── Configuración de catálogos (solo SuperAd) ───────────────────────────

    public function config()
    {
        $categorias = DB::table('bitacora_categorias')->orderBy('nombre')->get();
        $plantillas = DB::table('bitacora_plantillas as p')
            ->leftJoin('bitacora_categorias as c', 'c.id', '=', 'p.categoria_id')
            ->select('p.*', 'c.nombre as categoria_nombre')
            ->orderBy('p.id')
            ->get();

        return view('bitacora.config', compact('categorias', 'plantillas'));
    }

    public function storeCategoria(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'ambito' => 'required|in:academico,convivencia,general',
            'color'  => 'nullable|string|max:20',
        ]);
        $data['activo']   = 1;
        $data['docentes'] = $request->boolean('docentes');
        $data['unica']    = $request->boolean('unica');
        DB::table('bitacora_categorias')->insert($data);

        return back()->with('ok', 'Categoría creada.');
    }

    public function updateCategoria(Request $request, int $id)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'ambito' => 'required|in:academico,convivencia,general',
            'color'  => 'nullable|string|max:20',
            'activo' => 'nullable|boolean',
        ]);
        $data['activo']   = $request->boolean('activo');
        $data['docentes'] = $request->boolean('docentes');
        $data['unica']    = $request->boolean('unica');
        DB::table('bitacora_categorias')->where('id', $id)->update($data);

        return back()->with('ok', 'Categoría actualizada.');
    }

    public function destroyCategoria(int $id)
    {
        $enUso = DB::table('bitacora_entradas')->where('categoria_id', $id)->exists();
        if ($enUso) {
            // No se borra para no romper entradas existentes; se desactiva.
            DB::table('bitacora_categorias')->where('id', $id)->update(['activo' => 0]);
            return back()->with('ok', 'La categoría tiene observaciones asociadas: se desactivó en lugar de eliminarse.');
        }
        DB::table('bitacora_plantillas')->where('categoria_id', $id)->update(['categoria_id' => null]);
        DB::table('bitacora_categorias')->where('id', $id)->delete();

        return back()->with('ok', 'Categoría eliminada.');
    }

    public function storePlantilla(Request $request)
    {
        $data = $request->validate([
            'categoria_id' => 'nullable|integer',
            'texto'        => 'required|string|max:8000',
        ]);
        $data['categoria_id'] = $data['categoria_id'] ?: null;
        $data['activo'] = 1;
        DB::table('bitacora_plantillas')->insert($data);

        return back()->with('ok', 'Plantilla creada.');
    }

    public function updatePlantilla(Request $request, int $id)
    {
        $data = $request->validate([
            'categoria_id' => 'nullable|integer',
            'texto'        => 'required|string|max:8000',
            'activo'       => 'nullable|boolean',
        ]);
        $data['categoria_id'] = $data['categoria_id'] ?: null;
        $data['activo'] = $request->boolean('activo');
        DB::table('bitacora_plantillas')->where('id', $id)->update($data);

        return back()->with('ok', 'Plantilla actualizada.');
    }

    public function destroyPlantilla(int $id)
    {
        DB::table('bitacora_plantillas')->where('id', $id)->delete();

        return back()->with('ok', 'Plantilla eliminada.');
    }
}
