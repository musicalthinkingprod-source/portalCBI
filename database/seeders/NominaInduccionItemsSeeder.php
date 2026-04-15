<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NominaInduccionItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('nomina_induccion_items')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('nomina_induccion_items')->insert([
            // ── LEGAL (todos) ───────────────────────────────────────
            ['seccion' => 'legal', 'nombre' => 'Documentación legal', 'tipo_empleado' => null, 'orden' => 1, 'activo' => true],
            ['seccion' => 'legal', 'nombre' => 'Entrega de insumos (computador y libros)', 'tipo_empleado' => null, 'orden' => 2, 'activo' => true],
            ['seccion' => 'legal', 'nombre' => 'Explicación de cargo', 'tipo_empleado' => null, 'orden' => 3, 'activo' => true],
            ['seccion' => 'legal', 'nombre' => 'Horarios laborales', 'tipo_empleado' => null, 'orden' => 4, 'activo' => true],
            ['seccion' => 'legal', 'nombre' => 'Dirección de grupo', 'tipo_empleado' => 'docente', 'orden' => 5, 'activo' => true],
            ['seccion' => 'legal', 'nombre' => 'Recorrido por las instalaciones', 'tipo_empleado' => null, 'orden' => 6, 'activo' => true],

            // ── ADMINISTRATIVA (todos) ───────────────────────────────
            ['seccion' => 'administrativa', 'nombre' => 'Horarios laborales y control de asistencia', 'tipo_empleado' => null, 'orden' => 1, 'activo' => true],
            ['seccion' => 'administrativa', 'nombre' => 'Uso de plataformas', 'tipo_empleado' => null, 'orden' => 2, 'activo' => true],
            ['seccion' => 'administrativa', 'nombre' => 'Políticas internas y reglamento docente', 'tipo_empleado' => 'docente', 'orden' => 3, 'activo' => true],
            ['seccion' => 'administrativa', 'nombre' => 'Políticas internas y reglamento interno', 'tipo_empleado' => null, 'orden' => 4, 'activo' => true],
            ['seccion' => 'administrativa', 'nombre' => 'Canales de comunicación', 'tipo_empleado' => null, 'orden' => 5, 'activo' => true],

            // ── ACADÉMICA (docentes, coordinadores, orientadores) ────
            ['seccion' => 'academica', 'nombre' => 'Plan de estudios', 'tipo_empleado' => 'docente', 'orden' => 1, 'activo' => true],
            ['seccion' => 'academica', 'nombre' => 'Modelo pedagógico', 'tipo_empleado' => 'docente', 'orden' => 2, 'activo' => true],
            ['seccion' => 'academica', 'nombre' => 'Lineamientos de evaluación', 'tipo_empleado' => 'docente', 'orden' => 3, 'activo' => true],
            ['seccion' => 'academica', 'nombre' => 'Planeación de clases', 'tipo_empleado' => 'docente', 'orden' => 4, 'activo' => true],
            ['seccion' => 'academica', 'nombre' => 'Metodología de enseñanza esperada', 'tipo_empleado' => 'docente', 'orden' => 5, 'activo' => true],
            ['seccion' => 'academica', 'nombre' => 'Plan de estudios (revisión)', 'tipo_empleado' => 'coordinador', 'orden' => 1, 'activo' => true],
            ['seccion' => 'academica', 'nombre' => 'Modelo pedagógico (revisión)', 'tipo_empleado' => 'coordinador', 'orden' => 2, 'activo' => true],
            ['seccion' => 'academica', 'nombre' => 'Lineamientos de evaluación (revisión)', 'tipo_empleado' => 'coordinador', 'orden' => 3, 'activo' => true],

            // ── CONVIVENCIAL (docentes, coordinadores, orientadores) ─
            ['seccion' => 'convivencial', 'nombre' => 'Normas de disciplina y manual de convivencia', 'tipo_empleado' => 'docente', 'orden' => 1, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Procedimientos ante conflictos o problemas estudiantiles', 'tipo_empleado' => 'docente', 'orden' => 2, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Manejo de padres de familia', 'tipo_empleado' => 'docente', 'orden' => 3, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Protocolos de convivencia escolar', 'tipo_empleado' => 'docente', 'orden' => 4, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Manual de convivencia (gestión)', 'tipo_empleado' => 'coordinador', 'orden' => 1, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Protocolos de convivencia escolar', 'tipo_empleado' => 'coordinador', 'orden' => 2, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Manual de convivencia', 'tipo_empleado' => 'orientador', 'orden' => 1, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Procedimientos ante conflictos o problemas estudiantiles', 'tipo_empleado' => 'orientador', 'orden' => 2, 'activo' => true],
            ['seccion' => 'convivencial', 'nombre' => 'Manejo de padres de familia', 'tipo_empleado' => 'orientador', 'orden' => 3, 'activo' => true],
        ]);
    }
}
