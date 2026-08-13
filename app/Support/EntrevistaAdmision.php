<?php

namespace App\Support;

/**
 * Estructura de la Entrevista de Admisión (formato sugerido 2026).
 *
 * Los datos personales (sección I) viven en columnas propias de la tabla
 * `admision_entrevistas` porque se usan para buscar y para el reporte de
 * rectoría. De la sección II en adelante todo se guarda en el JSON
 * `respuestas`, indexado por la llave de cada pregunta.
 */
class EntrevistaAdmision
{
    /** Semáforo que el orientador marca por área. */
    const SEMAFORO = [
        'ok'       => ['texto' => 'Sin novedad',    'color' => 'green'],
        'atencion' => ['texto' => 'Requiere atención', 'color' => 'amber'],
        'alerta'   => ['texto' => 'Alerta',          'color' => 'red'],
    ];

    /** Concepto de viabilidad de ingreso (sección VIII). */
    const VIABILIDAD = [
        'viable'      => 'Viable',
        'condicionado' => 'Viable con compromisos',
        'no_viable'   => 'No viable',
    ];

    /** Decisión final de rectoría sobre el aspirante. */
    const DECISION_RECTORIA = [
        'admitido'     => 'Admitido',
        'condicionado' => 'Admitido con compromisos',
        'aplazado'     => 'Aplazado',
        'no_admitido'  => 'No admitido',
    ];

    /**
     * Secciones II a VIII del formato sugerido.
     *
     * Cada pregunta: k = llave en el JSON, t = enunciado, filas = alto del textarea.
     */
    public static function secciones(): array
    {
        return [
            'salud' => [
                'num'    => 'II',
                'titulo' => 'Antecedentes de salud y desarrollo',
                'icono'  => '🩺',
                'preguntas' => [
                    ['k' => 'medicos',       't' => 'Antecedentes médicos relevantes, incluyendo diagnósticos, tratamientos actuales y medicación si aplica.', 'filas' => 3],
                    ['k' => 'apoyos',        't' => 'Ha recibido o recibe actualmente apoyo de psicología, neuropsicología, fonoaudiología, terapia ocupacional u otro profesional. Motivo y frecuencia.', 'filas' => 3],
                    ['k' => 'diagnostico',   't' => 'Cuenta con diagnóstico o valoración de dificultades de aprendizaje, atención, lenguaje o desarrollo. Existe documento soporte disponible.', 'filas' => 3],
                    ['k' => 'familiares',    't' => 'Antecedentes familiares de salud mental o condiciones del neurodesarrollo relevantes para el acompañamiento escolar.', 'filas' => 2],
                    ['k' => 'rutinas',       't' => 'Rutinas de sueño, alimentación y actividad física actuales.', 'filas' => 2],
                    ['k' => 'ultimo_control', 't' => 'Último control médico realizado y resultado general (peso, talla, estado de salud).', 'filas' => 2],
                    ['k' => 'esfinteres',    't' => 'Control de esfínteres, en caso de aplicar por la edad.', 'filas' => 1],
                    ['k' => 'alergias',      't' => 'Alergias, restricciones alimentarias o condiciones médicas que el colegio deba tener en cuenta.', 'filas' => 2],
                ],
            ],

            'academica' => [
                'num'    => 'III',
                'titulo' => 'Área académica',
                'icono'  => '📚',
                'preguntas' => [
                    ['k' => 'fortalezas',   't' => 'Áreas o asignaturas en las que el estudiante muestra mayor fortaleza.', 'filas' => 2],
                    ['k' => 'dificultades', 't' => 'Áreas en las que ha presentado dificultades históricas.', 'filas' => 2],
                    ['k' => 'estilo',       't' => 'Estilo de aprendizaje percibido por la familia. Visual, auditivo, kinestésico, o mixto.', 'filas' => 2],
                    ['k' => 'habitos',      't' => 'Hábitos de estudio actuales y nivel de autonomía frente a tareas y responsabilidades escolares.', 'filas' => 3],
                    ['k' => 'apoyo_externo', 't' => 'Ha recibido apoyo pedagógico externo, tutorías o refuerzo académico. En qué áreas.', 'filas' => 2],
                    ['k' => 'piar',         't' => 'Cuenta con Plan Individual de Ajustes Razonables (PIAR) u otro plan de apoyo vigente en su colegio anterior.', 'filas' => 2],
                ],
            ],

            'socioemocional' => [
                'num'    => 'IV',
                'titulo' => 'Área socioemocional y de comportamiento',
                'icono'  => '💬',
                'preguntas' => [
                    ['k' => 'temperamento', 't' => 'Cómo describe la familia el temperamento y la personalidad del estudiante.', 'filas' => 3],
                    ['k' => 'frustracion',  't' => 'Cómo maneja la frustración, los cambios de rutina y las situaciones nuevas.', 'filas' => 3],
                    ['k' => 'senales',      't' => 'Ha mostrado señales de ansiedad, tristeza persistente, aislamiento o cambios bruscos de comportamiento en el último año.', 'filas' => 3],
                    ['k' => 'pares',        't' => 'Cómo son sus relaciones con pares. Facilidad o dificultad para hacer amigos, resolver conflictos, trabajar en equipo.', 'filas' => 3],
                    ['k' => 'acoso',        't' => 'Ha estado involucrado en situaciones de acoso escolar, ya sea como afectado, agresor u observador.', 'filas' => 3],
                    ['k' => 'disciplina',   't' => 'Qué estrategias disciplinarias utiliza la familia en casa y cómo responde el estudiante a ellas.', 'filas' => 3],
                    ['k' => 'tiempo_libre', 't' => 'Qué actividades disfruta en su tiempo libre y cómo las equilibra con el uso de pantallas.', 'filas' => 2],
                ],
            ],

            'tecnologia' => [
                'num'    => 'V',
                'titulo' => 'Uso de tecnología y redes sociales',
                'icono'  => '📱',
                'preguntas' => [
                    ['k' => 'edad_inicio', 't' => 'Edad a la que comenzó a usar dispositivos electrónicos de forma independiente.', 'filas' => 1],
                    ['k' => 'pantalla',    't' => 'Tiempo aproximado de pantalla diario y tipo de contenido que consume.', 'filas' => 2],
                    ['k' => 'redes',       't' => 'Tiene redes sociales propias. Cuáles y con qué supervisión.', 'filas' => 2],
                    ['k' => 'normas',      't' => 'Existen normas familiares establecidas para el uso de tecnología.', 'filas' => 2],
                ],
            ],

            'familiar' => [
                'num'    => 'VI',
                'titulo' => 'Área familiar y contexto de convivencia',
                'icono'  => '🏠',
                'preguntas' => [
                    ['k' => 'dinamica',     't' => 'Estructura y dinámica familiar actual. Con quién vive el estudiante y quiénes conforman su red de apoyo cercana.', 'filas' => 3],
                    ['k' => 'situaciones',  't' => 'Existen situaciones familiares recientes que puedan afectar el bienestar emocional del estudiante, como separaciones, duelos, cambios de ciudad o de vivienda.', 'filas' => 3],
                    ['k' => 'custodia',     't' => 'Ambos padres o acudientes están involucrados en la vida escolar del estudiante. Existen restricciones legales o acuerdos de custodia que el colegio deba conocer.', 'filas' => 3],
                    ['k' => 'crianza',      't' => 'Cómo se distribuyen las responsabilidades de crianza y acompañamiento escolar entre los adultos a cargo.', 'filas' => 3],
                    ['k' => 'red_extendida', 't' => 'La familia cuenta con red de apoyo extendida, como abuelos, tíos u otros cuidadores involucrados en el día a día del estudiante.', 'filas' => 2],
                ],
            ],

            'expectativas' => [
                'num'    => 'VII',
                'titulo' => 'Expectativas de la familia',
                'icono'  => '🎯',
                'preguntas' => [
                    ['k' => 'eleccion',    't' => 'Qué motivó la elección de esta institución educativa.', 'filas' => 3],
                    ['k' => 'adaptacion',  't' => 'Qué expectativas tiene la familia frente al proceso de adaptación del estudiante.', 'filas' => 3],
                    ['k' => 'otros',       't' => 'Qué aspectos consideran importantes que el colegio conozca antes del ingreso, y que no hayan sido cubiertos en las preguntas anteriores.', 'filas' => 3],
                ],
            ],
        ];
    }

    /** Sección VIII: concepto de los entrevistadores (se maneja aparte en el form). */
    public static function concepto(): array
    {
        return [
            ['k' => 'conducta_estudiante', 't' => 'Comportamiento y disposición del estudiante durante la entrevista.', 'filas' => 3],
            ['k' => 'conducta_acudientes', 't' => 'Comportamiento y actitud de los acudientes durante la entrevista.', 'filas' => 3],
            ['k' => 'fortalezas',          't' => 'Fortalezas observadas.', 'filas' => 3],
            ['k' => 'alertas',             't' => 'Dificultades o señales de alerta observadas.', 'filas' => 3],
            ['k' => 'recomendaciones',     't' => 'Recomendaciones y compromisos de seguimiento, incluyendo remisiones a profesionales externos si aplica.', 'filas' => 3],
        ];
    }

    /** Todas las llaves válidas del JSON `respuestas` (secciones II–VIII). */
    public static function llaves(): array
    {
        $llaves = [];
        foreach (self::secciones() as $slug => $sec) {
            foreach ($sec['preguntas'] as $p) {
                $llaves[] = $slug . '.' . $p['k'];
            }
        }
        foreach (self::concepto() as $p) {
            $llaves[] = 'concepto.' . $p['k'];
        }
        return $llaves;
    }

    /** Deja solo las llaves conocidas y recorta el texto. */
    public static function normalizarRespuestas($input): array
    {
        $validas = array_flip(self::llaves());
        $out = [];
        foreach ((array) $input as $seccion => $preguntas) {
            foreach ((array) $preguntas as $k => $v) {
                $llave = $seccion . '.' . $k;
                if (!isset($validas[$llave])) {
                    continue;
                }
                $v = trim((string) $v);
                if ($v !== '') {
                    $out[$seccion][$k] = mb_substr($v, 0, 4000);
                }
            }
        }
        return $out;
    }

    /** Deja solo semáforos válidos por sección. */
    public static function normalizarAlertas($input): array
    {
        $secciones = array_keys(self::secciones());
        $out = [];
        foreach ((array) $input as $slug => $valor) {
            if (in_array($slug, $secciones, true) && isset(self::SEMAFORO[$valor])) {
                $out[$slug] = $valor;
            }
        }
        return $out;
    }

    /**
     * Normaliza el cuadro de composición familiar (repetidor).
     *
     * @return array<int, array{nombre:string,parentesco:string,edad:?int,ocupacion:string,positivo:string,negativo:string}>
     */
    public static function normalizarFamilia($input): array
    {
        $out = [];
        foreach ((array) $input as $fila) {
            $nombre = trim((string) ($fila['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $edad = trim((string) ($fila['edad'] ?? ''));
            $out[] = [
                'nombre'     => mb_substr($nombre, 0, 120),
                'parentesco' => mb_substr(trim((string) ($fila['parentesco'] ?? '')), 0, 60),
                'edad'       => $edad === '' ? null : (int) $edad,
                'ocupacion'  => mb_substr(trim((string) ($fila['ocupacion'] ?? '')), 0, 80),
                'positivo'   => mb_substr(trim((string) ($fila['positivo'] ?? '')), 0, 200),
                'negativo'   => mb_substr(trim((string) ($fila['negativo'] ?? '')), 0, 200),
            ];
        }
        return $out;
    }

    /**
     * Cuántas preguntas de la entrevista están diligenciadas (para la barra de avance).
     *
     * @return array{respondidas:int,total:int,porcentaje:float}
     */
    public static function avance(?array $respuestas): array
    {
        $respuestas = $respuestas ?: [];
        $total = count(self::llaves());
        $hechas = 0;
        foreach ($respuestas as $preguntas) {
            foreach ((array) $preguntas as $v) {
                if (trim((string) $v) !== '') {
                    $hechas++;
                }
            }
        }
        return [
            'respondidas' => $hechas,
            'total'       => $total,
            'porcentaje'  => $total ? round($hechas * 100 / $total, 1) : 0.0,
        ];
    }
}
