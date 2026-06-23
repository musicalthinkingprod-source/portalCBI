<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\NotificacionesController;

/**
 * Lógica de negocio de la Agenda Digital / Bitácora del estudiante.
 *
 * Concentra las escrituras y reglas "sí o sí" del módulo, desacopladas del
 * controlador (que solo resuelve permisos por perfil y arma la respuesta):
 *   - Trazabilidad: cada anotación guarda autor (registrado_por) y estudiante.
 *   - Contexto relacional: período académico actual; materia en registros de aula.
 *   - Carga masiva desacoplada: un payload → N inserciones individuales.
 *   - Severidad: prioridad 'normal' | 'alta' (default por categoría).
 *   - Inmutabilidad: una anotación con acknowledged_at no puede cambiar de contenido.
 */
class AgendaService
{
    public const PRIORIDAD_NORMAL = 'normal';
    public const PRIORIDAD_ALTA   = 'alta';

    public const ROL_STAFF     = 'staff';
    public const ROL_ACUDIENTE = 'acudiente';

    /**
     * Período académico (1..4) para una fecha dada, según calendario_academico.
     * Cada período abarca 7 ciclos y dia_ciclo=1 marca el inicio de cada ciclo.
     * Devuelve null si la fecha es anterior al primer inicio de ciclo del año.
     */
    public function periodoParaFecha(?string $fecha = null): ?int
    {
        $ref  = $fecha ?: today()->toDateString();
        $anio = (int) date('Y', strtotime($ref));

        $inicios = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->where('dia_ciclo', 1)
            ->orderBy('fecha')
            ->distinct()
            ->pluck('fecha')
            ->values();

        $global = null;
        foreach ($inicios as $idx => $ini) {
            if ($ini <= $ref) $global = $idx;
        }

        return $global === null ? null : (int) floor($global / 7) + 1;
    }

    /** Prioridad por defecto de una categoría (objeto de bitacora_categorias). */
    public function prioridadDeCategoria($categoria): string
    {
        $p = $categoria->prioridad ?? self::PRIORIDAD_NORMAL;
        return $this->normalizarPrioridad($p);
    }

    /** Solo se aceptan 'normal' | 'alta'; cualquier otra cosa cae a 'normal'. */
    public function normalizarPrioridad(?string $prioridad): string
    {
        return $prioridad === self::PRIORIDAD_ALTA ? self::PRIORIDAD_ALTA : self::PRIORIDAD_NORMAL;
    }

    /** ¿La familia ya acusó recibo? Si es así, la anotación es inmutable. */
    public function fueLeida($entrada): bool
    {
        return $entrada && !empty($entrada->acknowledged_at);
    }

    /**
     * Registra una anotación individual.
     *
     * $datos: codigo_alumno, categoria_id, fecha, observacion, prioridad,
     *         codigo_mat (nullable), registrado_por, registrado_nombre (nullable),
     *         es_unica (bool: categoría de registro único).
     *
     * @return array{accion:string, id:int}  accion: 'creada' | 'reemplazada'
     */
    public function registrar(array $datos): array
    {
        $codigo = (int) $datos['codigo_alumno'];
        $fecha  = $datos['fecha'];
        $catId  = (int) $datos['categoria_id'];
        $texto  = mb_substr(trim($datos['observacion']), 0, 8000);
        $anio   = (int) date('Y', strtotime($fecha));

        $base = [
            'codigo_alumno'  => $codigo,
            'categoria_id'   => $catId,
            'codigo_mat'     => $datos['codigo_mat'] ?? null,
            'fecha'          => $fecha,
            'anio'           => $anio,
            'periodo'        => $this->periodoParaFecha($fecha),
            'observacion'    => $texto,
            'prioridad'      => $this->normalizarPrioridad($datos['prioridad'] ?? null),
            'registrado_por' => $datos['registrado_por'],
        ];
        if (array_key_exists('registrado_nombre', $datos)) {
            $base['registrado_nombre'] = $datos['registrado_nombre'];
        }

        // Categoría "única" (ej. Consejo Académico): no se duplica; reemplaza el
        // registro previo de (estudiante, fecha, categoría) SI no fue leído aún.
        if (!empty($datos['es_unica'])) {
            $existente = DB::table('bitacora_entradas')
                ->where(['codigo_alumno' => $codigo, 'fecha' => $fecha, 'categoria_id' => $catId])
                ->first();

            if ($existente && !$this->fueLeida($existente)) {
                DB::table('bitacora_entradas')->where('id', $existente->id)->update([
                    'observacion'    => $texto,
                    'prioridad'      => $base['prioridad'],
                    'periodo'        => $base['periodo'],
                    'anio'           => $anio,
                    'registrado_por' => $datos['registrado_por'],
                ]);
                return ['accion' => 'reemplazada', 'id' => (int) $existente->id];
            }
        }

        $id = DB::table('bitacora_entradas')->insertGetId($base + ['created_at' => now()]);

        return ['accion' => 'creada', 'id' => (int) $id];
    }

    /**
     * Asignación grupal desacoplada.
     *
     * Recibe UN payload común (categoría, materia, fecha, prioridad, autor…) y un
     * mapa [codigo_alumno => texto], y produce un registro INDEPENDIENTE por
     * estudiante. Cada fila tiene su propio acknowledged_at, de modo que el acuse
     * de recibo de cada familia es individual.
     *
     * Reglas:
     *   - Solo se procesan códigos presentes en $codigosValidos.
     *   - Si ya existe (estudiante, fecha, categoría): se actualiza, salvo que ya
     *     haya sido leído (inmutable) → se cuenta como bloqueada y se omite.
     *   - Texto vacío: se elimina el registro previo de esa fecha/categoría, salvo
     *     que ya fuera leído → se omite.
     *
     * @param array $payload  categoria_id, fecha, prioridad, codigo_mat(nullable),
     *                        registrado_por, registrado_nombre(nullable)
     * @param array $observaciones  [codigo_alumno => texto]
     * @param int[] $codigosValidos
     * @return array{creadas:int, actualizadas:int, eliminadas:int, bloqueadas:int}
     */
    public function asignacionGrupal(array $payload, array $observaciones, array $codigosValidos): array
    {
        $catId     = (int) $payload['categoria_id'];
        $fecha     = $payload['fecha'];
        $anio      = (int) date('Y', strtotime($fecha));
        $periodo   = $this->periodoParaFecha($fecha);
        $prioridad = $this->normalizarPrioridad($payload['prioridad'] ?? null);

        $comun = [
            'categoria_id'   => $catId,
            'codigo_mat'     => $payload['codigo_mat'] ?? null,
            'fecha'          => $fecha,
            'anio'           => $anio,
            'periodo'        => $periodo,
            'prioridad'      => $prioridad,
            'registrado_por' => $payload['registrado_por'],
        ];
        if (array_key_exists('registrado_nombre', $payload)) {
            $comun['registrado_nombre'] = $payload['registrado_nombre'];
        }

        $resumen = ['creadas' => 0, 'actualizadas' => 0, 'eliminadas' => 0, 'bloqueadas' => 0];

        foreach ($observaciones as $codigo => $texto) {
            $codigo = (int) $codigo;
            if (!in_array($codigo, $codigosValidos, true)) continue;

            $texto = trim((string) $texto);
            $clave = ['codigo_alumno' => $codigo, 'fecha' => $fecha, 'categoria_id' => $catId];

            $existente = DB::table('bitacora_entradas')->where($clave)->first();

            // Inmutabilidad: si la familia ya acusó recibo, no se toca (ni borra ni edita).
            if ($existente && $this->fueLeida($existente)) {
                $resumen['bloqueadas']++;
                continue;
            }

            if ($texto === '') {
                if ($existente) {
                    DB::table('bitacora_entradas')->where('id', $existente->id)->delete();
                    $resumen['eliminadas']++;
                }
                continue;
            }

            $texto = mb_substr($texto, 0, 8000);

            if ($existente) {
                DB::table('bitacora_entradas')->where('id', $existente->id)->update($comun + [
                    'observacion' => $texto,
                ]);
                $resumen['actualizadas']++;
            } else {
                DB::table('bitacora_entradas')->insert($clave + $comun + [
                    'observacion' => $texto,
                    'created_at'  => now(),
                ]);
                $resumen['creadas']++;
            }
        }

        return $resumen;
    }

    /**
     * Asignación de una TAREA a un curso/grupo completo.
     *
     * A diferencia de asignacionGrupal(), aquí el texto es ÚNICO y compartido por
     * todo el grupo, y cada guardado AGREGA registros nuevos (no reemplaza): permite
     * varias tareas el mismo día/materia. Cada estudiante recibe su propia fila con
     * acknowledged_at independiente.
     *
     * @param array $payload  categoria_id, codigo_mat, fecha, observacion, prioridad,
     *                        registrado_por, registrado_nombre(nullable)
     * @param int[] $codigos  estudiantes destino (ya resueltos por curso/grupo)
     * @return int  cantidad de registros creados
     */
    public function asignarTarea(array $payload, array $codigos): int
    {
        $fecha   = $payload['fecha'];
        $anio    = (int) date('Y', strtotime($fecha));
        $periodo = $this->periodoParaFecha($fecha);
        $texto   = mb_substr(trim($payload['observacion']), 0, 8000);
        $ahora   = now();

        $comun = [
            'categoria_id'   => (int) $payload['categoria_id'],
            'codigo_mat'     => $payload['codigo_mat'] ?? null,
            'fecha'          => $fecha,
            'anio'           => $anio,
            'periodo'        => $periodo,
            'observacion'    => $texto,
            'prioridad'      => $this->normalizarPrioridad($payload['prioridad'] ?? null),
            'registrado_por' => $payload['registrado_por'],
            'created_at'     => $ahora,
        ];
        if (array_key_exists('registrado_nombre', $payload)) {
            $comun['registrado_nombre'] = $payload['registrado_nombre'];
        }

        $filas = [];
        foreach (array_unique(array_map('intval', $codigos)) as $codigo) {
            $filas[] = ['codigo_alumno' => $codigo] + $comun;
        }
        if ($filas) {
            DB::table('bitacora_entradas')->insert($filas);
        }

        return count($filas);
    }

    // ── Hilos de comentarios ────────────────────────────────────────────────

    /**
     * Agrega un comentario al hilo de una anotación.
     *
     * @param string $autorRol  ROL_STAFF | ROL_ACUDIENTE
     * @return int  id del comentario creado
     */
    public function comentar(int $entradaId, string $autorRol, ?string $autorId, ?string $autorNombre, string $mensaje): int
    {
        $id = DB::table('bitacora_comentarios')->insertGetId([
            'entrada_id'   => $entradaId,
            'autor_rol'    => $autorRol,
            'autor_id'     => $autorId,
            'autor_nombre' => $autorNombre,
            'mensaje'      => mb_substr(trim($mensaje), 0, 4000),
            'created_at'   => now(),
        ]);

        $this->notificarComentario($entradaId, $autorRol, $autorId, $autorNombre);

        return $id;
    }

    /**
     * Notifica (vía la campana del portal) al staff involucrado en el hilo cuando
     * llega un comentario nuevo: el autor de la anotación y el staff que ya había
     * comentado, salvo quien acaba de escribir. Pensado sobre todo para los
     * docentes, que revisan muchas agendas y necesitan saber quién les respondió.
     */
    private function notificarComentario(int $entradaId, string $autorRol, ?string $autorId, ?string $autorNombre): void
    {
        $entrada = DB::table('bitacora_entradas')->where('id', $entradaId)->first();
        if (!$entrada) return;

        // Destinatarios en USER: autor de la anotación + staff que ya comentaron
        $usersDestino = collect([$entrada->registrado_por])
            ->merge(
                DB::table('bitacora_comentarios')
                    ->where('entrada_id', $entradaId)
                    ->where('autor_rol', self::ROL_STAFF)
                    ->pluck('autor_id')
            )
            ->filter()
            ->unique();

        // No notificar a quien acaba de escribir (si es del staff)
        if ($autorRol === self::ROL_STAFF) {
            $usersDestino = $usersDestino->reject(fn($u) => $u === $autorId);
        }
        if ($usersDestino->isEmpty()) return;

        // Las notificaciones se direccionan por PROFILE, pero las anotaciones/comentarios
        // guardan el USER de login: hay que mapear USER → PROFILE.
        $perfiles = DB::table('PRINUSERS')
            ->whereIn('USER', $usersDestino->all())
            ->pluck('PROFILE', 'USER');

        $est = DB::table('ESTUDIANTES')->where('CODIGO', $entrada->codigo_alumno)->first();
        $nombreEst = $est
            ? preg_replace('/\s+/', ' ', trim(($est->NOMBRE1 ?? '') . ' ' . ($est->APELLIDO1 ?? '')))
            : ('estudiante ' . $entrada->codigo_alumno);

        $titulo  = 'Nueva respuesta en la agenda';
        $mensaje = ($autorNombre ?: 'Alguien') . ' respondió en la anotación de ' . ($nombreEst ?: ('estudiante ' . $entrada->codigo_alumno)) . '.';
        $url     = route('bitacora.index', ['f_codigo' => $entrada->codigo_alumno]);

        foreach ($usersDestino as $u) {
            if ($perfil = $perfiles[$u] ?? null) {
                NotificacionesController::crear($perfil, 'agenda_hilo', $titulo, $mensaje, $url);
            }
        }
    }

    /**
     * Comentarios de un conjunto de anotaciones, en orden cronológico,
     * agrupados por entrada_id para pintar cada hilo junto a su anotación.
     */
    public function comentariosPorEntrada(array $entradaIds): \Illuminate\Support\Collection
    {
        if (empty($entradaIds)) return collect();

        return DB::table('bitacora_comentarios')
            ->whereIn('entrada_id', $entradaIds)
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->groupBy('entrada_id');
    }

    /** Borra un comentario solo si lo escribió el mismo autor (rol + id). */
    public function borrarComentario(int $id, string $autorRol, ?string $autorId): bool
    {
        return (bool) DB::table('bitacora_comentarios')
            ->where('id', $id)
            ->where('autor_rol', $autorRol)
            ->where('autor_id', $autorId)
            ->delete();
    }

    /**
     * Acuse de recibo de la familia. Marca acknowledged_at solo si está vacío
     * (idempotente: no se "re-acusa" ni se mueve la fecha original).
     *
     * @return bool  true si pasó de pendiente a confirmada en esta llamada.
     */
    public function confirmarLectura(int $entradaId, int $codigoAlumno): bool
    {
        $afectadas = DB::table('bitacora_entradas')
            ->where('id', $entradaId)
            ->where('codigo_alumno', $codigoAlumno)   // la familia solo confirma lo suyo
            ->whereNull('acknowledged_at')
            ->update(['acknowledged_at' => now()]);

        return $afectadas > 0;
    }
}
