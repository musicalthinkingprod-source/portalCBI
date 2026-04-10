@extends('layouts.app-sidebar')

@section('header', 'Gestor de Nómina')

@section('slot')

{{-- ============================================================
     DATOS MOCK (maqueta – sin base de datos)
     ============================================================ --}}
@php
$empleados = [
    [
        'id'         => 1,
        'nombre'     => 'Laura Milena Vargas Ospina',
        'cedula'     => '1.015.432.789',
        'cargo'      => 'Docente de Matemáticas',
        'area'       => 'Académica',
        'tipo'       => 'Tiempo completo',
        'estado'     => 'Activo',
        'ingreso'    => '2019-02-01',
        'salario'    => 2_850_000,
        'email'      => 'l.vargas@cbi.edu.co',
        'celular'    => '310 456 7890',
        'eps'        => 'Sura EPS',
        'pension'    => 'Porvenir',
        'arl'        => 'Positiva',
        'color'      => 'blue',
    ],
    [
        'id'         => 2,
        'nombre'     => 'Carlos Andrés Peña Ríos',
        'cedula'     => '79.654.321',
        'cargo'      => 'Coordinador Académico',
        'area'       => 'Directiva',
        'tipo'       => 'Tiempo completo',
        'estado'     => 'Activo',
        'ingreso'    => '2017-08-15',
        'salario'    => 3_500_000,
        'email'      => 'c.pena@cbi.edu.co',
        'celular'    => '315 123 4567',
        'eps'        => 'Nueva EPS',
        'pension'    => 'Colpensiones',
        'arl'        => 'Sura',
        'color'      => 'indigo',
    ],
    [
        'id'         => 3,
        'nombre'     => 'Sandra Patricia López Cruz',
        'cedula'     => '52.876.543',
        'cargo'      => 'Secretaria Académica',
        'area'       => 'Administrativa',
        'tipo'       => 'Tiempo completo',
        'estado'     => 'Activo',
        'ingreso'    => '2021-03-10',
        'salario'    => 2_100_000,
        'email'      => 's.lopez@cbi.edu.co',
        'celular'    => '321 987 6543',
        'eps'        => 'Compensar',
        'pension'    => 'Protección',
        'arl'        => 'Positiva',
        'color'      => 'purple',
    ],
    [
        'id'         => 4,
        'nombre'     => 'Jhon Sebastián Moreno Díaz',
        'cedula'     => '1.020.765.432',
        'cargo'      => 'Auxiliar de Sistemas',
        'area'       => 'Tecnología',
        'tipo'       => 'Medio tiempo',
        'estado'     => 'Inactivo',
        'ingreso'    => '2022-06-01',
        'salario'    => 1_300_000,
        'email'      => 'j.moreno@cbi.edu.co',
        'celular'    => '304 234 5678',
        'eps'        => 'Famisanar',
        'pension'    => 'Porvenir',
        'arl'        => 'Bolívar',
        'color'      => 'teal',
    ],
];

$entrega = [
    1 => [
        ['item' => 'Computador portátil',       'descripcion' => 'Lenovo ThinkPad E14 – SN: LNV2024081',  'estado' => 'Entregado',   'fecha' => '2019-02-01'],
        ['item' => 'Cargador portátil',          'descripcion' => 'Cargador original 65W',                 'estado' => 'Entregado',   'fecha' => '2019-02-01'],
        ['item' => 'Carnet institucional',       'descripcion' => 'Carnet laminado vigencia 2026',         'estado' => 'Entregado',   'fecha' => '2024-01-15'],
        ['item' => 'Llave aula de clases',       'descripcion' => 'Aula 201 – piso 2',                    'estado' => 'Entregado',   'fecha' => '2019-02-01'],
        ['item' => 'Manual de funciones',        'descripcion' => 'Versión 3.0 firmada',                   'estado' => 'Entregado',   'fecha' => '2019-02-01'],
        ['item' => 'Contrato laboral firmado',   'descripcion' => 'Contrato a término indefinido',         'estado' => 'Entregado',   'fecha' => '2019-02-01'],
        ['item' => 'Celular corporativo',        'descripcion' => 'No aplica para este cargo',             'estado' => 'No aplica',   'fecha' => '—'],
        ['item' => 'Acceso plataforma digital',  'descripcion' => 'Usuario en Portal Cebeista activo',     'estado' => 'Entregado',   'fecha' => '2019-02-01'],
        ['item' => 'Llave casillero personal',   'descripcion' => 'Casillero #12 sala de docentes',        'estado' => 'Pendiente',   'fecha' => '—'],
    ],
    2 => [
        ['item' => 'Computador de escritorio',   'descripcion' => 'HP EliteDesk 800 – SN: HP20230045',    'estado' => 'Entregado',   'fecha' => '2017-08-15'],
        ['item' => 'Monitor 24"',                'descripcion' => 'LG 24MK430H',                          'estado' => 'Entregado',   'fecha' => '2017-08-15'],
        ['item' => 'Teclado y ratón',            'descripcion' => 'Kit HP inalámbrico',                   'estado' => 'Entregado',   'fecha' => '2017-08-15'],
        ['item' => 'Carnet institucional',       'descripcion' => 'Carnet laminado vigencia 2026',         'estado' => 'Entregado',   'fecha' => '2024-01-15'],
        ['item' => 'Llave oficina coordinación', 'descripcion' => 'Oficina piso 1',                       'estado' => 'Entregado',   'fecha' => '2017-08-15'],
        ['item' => 'Manual de funciones',        'descripcion' => 'Versión 3.0 firmada',                   'estado' => 'Entregado',   'fecha' => '2017-08-15'],
        ['item' => 'Contrato laboral firmado',   'descripcion' => 'Contrato a término indefinido',         'estado' => 'Entregado',   'fecha' => '2017-08-15'],
        ['item' => 'Celular corporativo',        'descripcion' => 'Motorola G54 – SN: MOT2023-002',       'estado' => 'Entregado',   'fecha' => '2021-01-10'],
        ['item' => 'Acceso plataforma digital',  'descripcion' => 'Usuario en Portal Cebeista activo',     'estado' => 'Entregado',   'fecha' => '2017-08-15'],
        ['item' => 'Llave casillero personal',   'descripcion' => 'Casillero #3 oficina directiva',        'estado' => 'Entregado',   'fecha' => '2017-08-15'],
    ],
    3 => [
        ['item' => 'Computador de escritorio',   'descripcion' => 'Dell Optiplex 3080 – SN: DEL21090A',   'estado' => 'Entregado',   'fecha' => '2021-03-10'],
        ['item' => 'Monitor 22"',                'descripcion' => 'Samsung LS22',                         'estado' => 'Entregado',   'fecha' => '2021-03-10'],
        ['item' => 'Teclado y ratón',            'descripcion' => 'Kit alámbrico Dell',                   'estado' => 'Entregado',   'fecha' => '2021-03-10'],
        ['item' => 'Carnet institucional',       'descripcion' => 'Carnet laminado vigencia 2026',         'estado' => 'Entregado',   'fecha' => '2024-01-15'],
        ['item' => 'Sello y cuño secretaría',    'descripcion' => 'Sello húmedo oficial CBI',             'estado' => 'Entregado',   'fecha' => '2021-03-10'],
        ['item' => 'Llave oficina secretaría',   'descripcion' => 'Oficina secretaría piso 1',            'estado' => 'Entregado',   'fecha' => '2021-03-10'],
        ['item' => 'Manual de funciones',        'descripcion' => 'Versión 3.0 firmada',                   'estado' => 'Entregado',   'fecha' => '2021-03-10'],
        ['item' => 'Contrato laboral firmado',   'descripcion' => 'Contrato a término indefinido',         'estado' => 'Entregado',   'fecha' => '2021-03-10'],
        ['item' => 'Celular corporativo',        'descripcion' => 'No aplica para este cargo',             'estado' => 'No aplica',   'fecha' => '—'],
        ['item' => 'Acceso plataforma digital',  'descripcion' => 'Usuario en Portal Cebeista activo',     'estado' => 'Entregado',   'fecha' => '2021-03-10'],
    ],
    4 => [
        ['item' => 'Computador portátil',        'descripcion' => 'Asus VivoBook 15 – SN: ASU2022099',    'estado' => 'Entregado',   'fecha' => '2022-06-01'],
        ['item' => 'Herramientas de red',        'descripcion' => 'Kit crimpeadora y destornilladores',   'estado' => 'Entregado',   'fecha' => '2022-06-01'],
        ['item' => 'Carnet institucional',       'descripcion' => 'Venció – Pendiente renovación',        'estado' => 'Pendiente',   'fecha' => '—'],
        ['item' => 'Llave sala de sistemas',     'descripcion' => 'Sala de sistemas piso 2',              'estado' => 'Entregado',   'fecha' => '2022-06-01'],
        ['item' => 'Manual de funciones',        'descripcion' => 'Versión 3.0 firmada',                   'estado' => 'Entregado',   'fecha' => '2022-06-01'],
        ['item' => 'Contrato laboral firmado',   'descripcion' => 'Contrato a término fijo',               'estado' => 'Entregado',   'fecha' => '2022-06-01'],
        ['item' => 'Celular corporativo',        'descripcion' => 'No aplica para este cargo',             'estado' => 'No aplica',   'fecha' => '—'],
        ['item' => 'Acceso plataforma digital',  'descripcion' => 'Usuario en Portal Cebeista INACTIVO',   'estado' => 'Pendiente',   'fecha' => '—'],
    ],
];

$novedades = [
    1 => [
        ['tipo' => 'Vacaciones',     'descripcion' => 'Vacaciones periodo jun–jul 2025',          'dias' => 15, 'desde' => '2025-06-23', 'hasta' => '2025-07-07', 'estado' => 'Aprobado'],
        ['tipo' => 'Permiso',        'descripcion' => 'Cita médica EPS',                          'dias' => 1,  'desde' => '2025-09-12', 'hasta' => '2025-09-12', 'estado' => 'Aprobado'],
        ['tipo' => 'Incapacidad',    'descripcion' => 'Gripe – certificado médico adjunto',       'dias' => 3,  'desde' => '2025-11-03', 'hasta' => '2025-11-05', 'estado' => 'Aprobado'],
    ],
    2 => [
        ['tipo' => 'Vacaciones',     'descripcion' => 'Vacaciones diciembre 2024',                'dias' => 15, 'desde' => '2024-12-16', 'hasta' => '2024-12-30', 'estado' => 'Aprobado'],
        ['tipo' => 'Comisión',       'descripcion' => 'Capacitación MEN – Bogotá',               'dias' => 2,  'desde' => '2025-04-10', 'hasta' => '2025-04-11', 'estado' => 'Pendiente'],
    ],
    3 => [
        ['tipo' => 'Vacaciones',     'descripcion' => 'Vacaciones periodo jun–jul 2025',          'dias' => 15, 'desde' => '2025-06-23', 'hasta' => '2025-07-07', 'estado' => 'Aprobado'],
        ['tipo' => 'Permiso',        'descripcion' => 'Diligencia personal (sin remuneración)',   'dias' => 1,  'desde' => '2025-08-28', 'hasta' => '2025-08-28', 'estado' => 'Aprobado'],
    ],
    4 => [
        ['tipo' => 'Incapacidad',    'descripcion' => 'Fractura muñeca derecha',                  'dias' => 20, 'desde' => '2025-10-01', 'hasta' => '2025-10-20', 'estado' => 'Aprobado'],
        ['tipo' => 'Retiro',         'descripcion' => 'Proceso de desvinculación iniciado',       'dias' => 0,  'desde' => '2025-12-31', 'hasta' => '—',           'estado' => 'En proceso'],
    ],
];
@endphp

{{-- ============================================================
     LAYOUT PRINCIPAL
     ============================================================ --}}
<div x-data="{ empSeleccionado: 1, tabActiva: 'datos' }" class="flex gap-6 h-full">

    {{-- ── PANEL IZQUIERDO: lista de empleados ─────────────────────── --}}
    <div class="w-72 shrink-0 flex flex-col gap-3">

        {{-- Buscador --}}
        <div class="relative">
            <span class="absolute left-3 top-2.5 text-gray-400 text-sm">🔍</span>
            <input type="text" placeholder="Buscar empleado…"
                class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white shadow-sm">
        </div>

        {{-- Botón nuevo empleado --}}
        <button class="flex items-center justify-center gap-2 w-full bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded-xl transition shadow">
            ➕ Nuevo empleado
        </button>

        {{-- Tarjetas --}}
        @foreach($empleados as $emp)
        <div @click="empSeleccionado = {{ $emp['id'] }}; tabActiva = 'datos'"
             :class="empSeleccionado === {{ $emp['id'] }} ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-white hover:bg-gray-50'"
             class="rounded-xl shadow-sm p-4 cursor-pointer transition border border-gray-100">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-{{ $emp['color'] }}-100 text-{{ $emp['color'] }}-700 flex items-center justify-center font-bold text-sm shrink-0">
                    {{ strtoupper(substr($emp['nombre'], 0, 1)) }}{{ strtoupper(substr(explode(' ', $emp['nombre'])[2] ?? 'X', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 leading-tight truncate">{{ $emp['nombre'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $emp['cargo'] }}</p>
                    <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $emp['estado'] === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ $emp['estado'] }}
                    </span>
                </div>
            </div>
        </div>
        @endforeach

    </div>

    {{-- ── PANEL DERECHO: ficha del empleado ───────────────────────── --}}
    @foreach($empleados as $emp)
    <div x-show="empSeleccionado === {{ $emp['id'] }}" class="flex-1 min-w-0 flex flex-col gap-4">

        {{-- Header de la ficha --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-5">
            <div class="w-16 h-16 rounded-full bg-{{ $emp['color'] }}-100 text-{{ $emp['color'] }}-700 flex items-center justify-center text-2xl font-bold shrink-0">
                {{ strtoupper(substr($emp['nombre'], 0, 1)) }}{{ strtoupper(substr(explode(' ', $emp['nombre'])[2] ?? 'X', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-gray-800 leading-tight">{{ $emp['nombre'] }}</h2>
                <p class="text-sm text-gray-500">{{ $emp['cargo'] }} · {{ $emp['area'] }}</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $emp['tipo'] }}</span>
                    <span class="text-xs bg-{{ $emp['color'] }}-100 text-{{ $emp['color'] }}-700 px-2 py-0.5 rounded-full">Ingreso: {{ \Carbon\Carbon::parse($emp['ingreso'])->format('d/m/Y') }}</span>
                    <span class="text-xs {{ $emp['estado'] === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }} px-2 py-0.5 rounded-full font-semibold">{{ $emp['estado'] }}</span>
                </div>
            </div>
            <div class="text-right shrink-0">
                <p class="text-xs text-gray-400 uppercase tracking-wide">Salario base</p>
                <p class="text-xl font-bold text-gray-800">$ {{ number_format($emp['salario'], 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">mensual</p>
            </div>
        </div>

        {{-- Pestañas --}}
        <div class="bg-white rounded-xl shadow overflow-hidden flex-1 flex flex-col">

            {{-- Nav de pestañas --}}
            <div class="flex border-b border-gray-200 overflow-x-auto shrink-0">
                @foreach([
                    ['id' => 'datos',    'label' => '👤 Datos personales'],
                    ['id' => 'cargo',    'label' => '📦 Entrega de cargo'],
                    ['id' => 'nomina',   'label' => '💰 Nómina'],
                    ['id' => 'docs',     'label' => '📄 Documentos'],
                    ['id' => 'novedades','label' => '📅 Novedades'],
                ] as $tab)
                <button @click="tabActiva = '{{ $tab['id'] }}'"
                    :class="tabActiva === '{{ $tab['id'] }}' ? 'border-b-2 border-blue-600 text-blue-700 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-700'"
                    class="px-5 py-3 text-sm whitespace-nowrap transition">
                    {{ $tab['label'] }}
                </button>
                @endforeach
            </div>

            {{-- ── TAB: Datos personales ──────────────────────────── --}}
            <div x-show="tabActiva === 'datos'" class="p-6 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="space-y-4">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Información básica</h3>
                        @foreach([
                            ['label' => 'Nombre completo',      'val' => $emp['nombre']],
                            ['label' => 'Cédula de ciudadanía', 'val' => $emp['cedula']],
                            ['label' => 'Cargo',                'val' => $emp['cargo']],
                            ['label' => 'Área / Departamento',  'val' => $emp['area']],
                            ['label' => 'Tipo de vinculación',  'val' => $emp['tipo']],
                            ['label' => 'Fecha de ingreso',     'val' => \Carbon\Carbon::parse($emp['ingreso'])->format('d/m/Y')],
                        ] as $campo)
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs text-gray-400">{{ $campo['label'] }}</span>
                            <span class="text-sm font-medium text-gray-800">{{ $campo['val'] }}</span>
                        </div>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Contacto y seguridad social</h3>
                        @foreach([
                            ['label' => 'Correo institucional', 'val' => $emp['email']],
                            ['label' => 'Celular',              'val' => $emp['celular']],
                            ['label' => 'EPS',                  'val' => $emp['eps']],
                            ['label' => 'Pensión',              'val' => $emp['pension']],
                            ['label' => 'ARL',                  'val' => $emp['arl']],
                        ] as $campo)
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs text-gray-400">{{ $campo['label'] }}</span>
                            <span class="text-sm font-medium text-gray-800">{{ $campo['val'] }}</span>
                        </div>
                        @endforeach
                    </div>

                </div>

                {{-- Botones de acción --}}
                <div class="mt-6 flex gap-3 flex-wrap">
                    <button class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        ✏️ Editar datos
                    </button>
                    <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                        🖨️ Imprimir ficha
                    </button>
                </div>
            </div>

            {{-- ── TAB: Entrega de cargo ──────────────────────────── --}}
            <div x-show="tabActiva === 'cargo'" class="p-6 overflow-y-auto">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700 text-sm">Control de entrega de cargo</h3>
                    <button class="text-sm bg-blue-800 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg font-semibold transition">
                        ➕ Agregar ítem
                    </button>
                </div>

                @php
                    $items = $entrega[$emp['id']];
                    $entregados = count(array_filter($items, fn($i) => $i['estado'] === 'Entregado'));
                    $pendientes = count(array_filter($items, fn($i) => $i['estado'] === 'Pendiente'));
                    $noAplica   = count(array_filter($items, fn($i) => $i['estado'] === 'No aplica'));
                    $pct = round($entregados / count($items) * 100);
                @endphp

                {{-- Resumen --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-green-700">{{ $entregados }}</p>
                        <p class="text-xs text-green-600 mt-1">Entregados</p>
                    </div>
                    <div class="bg-yellow-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-600">{{ $pendientes }}</p>
                        <p class="text-xs text-yellow-600 mt-1">Pendientes</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-500">{{ $noAplica }}</p>
                        <p class="text-xs text-gray-500 mt-1">No aplica</p>
                    </div>
                </div>

                {{-- Barra de progreso --}}
                <div class="mb-5">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Completitud del cargo</span>
                        <span class="font-semibold">{{ $pct }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-2 bg-green-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                {{-- Tabla de ítems --}}
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                                <th class="text-left px-4 py-3">Ítem</th>
                                <th class="text-left px-4 py-3">Descripción / Serial</th>
                                <th class="text-left px-4 py-3">Estado</th>
                                <th class="text-left px-4 py-3">Fecha entrega</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $item['item'] }}</td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $item['descripcion'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full
                                        @if($item['estado'] === 'Entregado')  bg-green-100 text-green-700
                                        @elseif($item['estado'] === 'Pendiente') bg-yellow-100 text-yellow-700
                                        @else bg-gray-100 text-gray-500
                                        @endif">
                                        @if($item['estado'] === 'Entregado')  ✅
                                        @elseif($item['estado'] === 'Pendiente') ⏳
                                        @else —
                                        @endif
                                        {{ $item['estado'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $item['fecha'] }}</td>
                                <td class="px-4 py-3">
                                    <button class="text-xs text-blue-600 hover:underline">Editar</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex gap-3">
                    <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                        🖨️ Imprimir acta de entrega
                    </button>
                </div>
            </div>

            {{-- ── TAB: Nómina ────────────────────────────────────── --}}
            <div x-show="tabActiva === 'nomina'" class="p-6 overflow-y-auto">
                @php
                    $salario   = $emp['salario'];
                    $aux_trans = 200000;
                    $bonif     = 150000;
                    $devengado = $salario + $aux_trans + $bonif;
                    $salud     = round($salario * 0.04);
                    $pension   = round($salario * 0.04);
                    $retefuente= 0;
                    $descuentos= $salud + $pension + $retefuente;
                    $neto      = $devengado - $descuentos;
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                    <div class="bg-blue-50 rounded-xl p-5">
                        <p class="text-xs text-blue-500 uppercase tracking-wide mb-1">Total devengado</p>
                        <p class="text-2xl font-bold text-blue-800">$ {{ number_format($devengado, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-5">
                        <p class="text-xs text-red-500 uppercase tracking-wide mb-1">Total descuentos</p>
                        <p class="text-2xl font-bold text-red-700">$ {{ number_format($descuentos, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-5">
                        <p class="text-xs text-green-600 uppercase tracking-wide mb-1">Neto a pagar</p>
                        <p class="text-2xl font-bold text-green-800">$ {{ number_format($neto, 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Devengados --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Devengados</h3>
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach([
                                        ['Salario básico',         $salario],
                                        ['Aux. de transporte',     $aux_trans],
                                        ['Bonificación mensual',   $bonif],
                                    ] as [$concepto, $valor])
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-700">{{ $concepto }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-800">$ {{ number_format($valor, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="bg-blue-50">
                                        <td class="px-4 py-3 font-bold text-blue-800">Total devengado</td>
                                        <td class="px-4 py-3 text-right font-bold text-blue-800">$ {{ number_format($devengado, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Descuentos --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Descuentos</h3>
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach([
                                        ['Salud (4%)',             $salud],
                                        ['Pensión (4%)',           $pension],
                                        ['Retención en la fuente', $retefuente],
                                    ] as [$concepto, $valor])
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-700">{{ $concepto }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-800">$ {{ number_format($valor, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="bg-red-50">
                                        <td class="px-4 py-3 font-bold text-red-700">Total descuentos</td>
                                        <td class="px-4 py-3 text-right font-bold text-red-700">$ {{ number_format($descuentos, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Aportes patronales --}}
                <div class="mt-6">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Aportes patronales (referencia)</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach([
                            ['Salud 8.5%',     round($salario * 0.085)],
                            ['Pensión 12%',    round($salario * 0.12)],
                            ['ARL 0.522%',     round($salario * 0.00522)],
                            ['Caja Familiar 4%', round($salario * 0.04)],
                        ] as [$nombre, $valor])
                        <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                            <p class="text-xs text-gray-400">{{ $nombre }}</p>
                            <p class="text-sm font-bold text-gray-700 mt-1">$ {{ number_format($valor, 0, ',', '.') }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 flex gap-3">
                    <button class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        💾 Liquidar nómina del mes
                    </button>
                    <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                        🖨️ Desprendible de pago
                    </button>
                </div>
            </div>

            {{-- ── TAB: Documentos ────────────────────────────────── --}}
            <div x-show="tabActiva === 'docs'" class="p-6 overflow-y-auto">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700 text-sm">Documentos del empleado</h3>
                    <button class="text-sm bg-blue-800 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg font-semibold transition">
                        📎 Subir documento
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach([
                        ['nombre' => 'Contrato laboral',              'tipo' => 'PDF',  'peso' => '320 KB', 'fecha' => '2019-02-01', 'estado' => 'Vigente'],
                        ['nombre' => 'Cédula de ciudadanía',          'tipo' => 'PDF',  'peso' => '180 KB', 'fecha' => '2019-02-01', 'estado' => 'Vigente'],
                        ['nombre' => 'Título profesional',            'tipo' => 'PDF',  'peso' => '1.2 MB', 'fecha' => '2019-02-01', 'estado' => 'Vigente'],
                        ['nombre' => 'Diploma de bachiller',          'tipo' => 'PDF',  'peso' => '850 KB', 'fecha' => '2019-02-01', 'estado' => 'Vigente'],
                        ['nombre' => 'Afiliación EPS',                'tipo' => 'PDF',  'peso' => '120 KB', 'fecha' => '2019-02-01', 'estado' => 'Vigente'],
                        ['nombre' => 'Afiliación pensión',            'tipo' => 'PDF',  'peso' => '115 KB', 'fecha' => '2019-02-01', 'estado' => 'Vigente'],
                        ['nombre' => 'Certificado de antecedentes',   'tipo' => 'PDF',  'peso' => '210 KB', 'fecha' => '2024-01-10', 'estado' => 'Vigente'],
                        ['nombre' => 'Examen médico de ingreso',      'tipo' => 'PDF',  'peso' => '450 KB', 'fecha' => '2019-01-28', 'estado' => 'Archivado'],
                    ] as $doc)
                    <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-200 hover:bg-gray-100 transition">
                        <div class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center font-bold text-xs shrink-0">
                            {{ $doc['tipo'] }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $doc['nombre'] }}</p>
                            <p class="text-xs text-gray-400">{{ $doc['peso'] }} · Subido {{ \Carbon\Carbon::parse($doc['fecha'])->format('d/m/Y') }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full shrink-0
                            {{ $doc['estado'] === 'Vigente' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">
                            {{ $doc['estado'] }}
                        </span>
                        <button class="text-blue-600 hover:text-blue-800 text-sm ml-1">⬇</button>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── TAB: Novedades ─────────────────────────────────── --}}
            <div x-show="tabActiva === 'novedades'" class="p-6 overflow-y-auto">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700 text-sm">Novedades de nómina</h3>
                    <button class="text-sm bg-blue-800 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg font-semibold transition">
                        ➕ Registrar novedad
                    </button>
                </div>

                {{-- Resumen de vacaciones --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    @foreach([
                        ['Días causados', '30', 'blue'],
                        ['Días tomados',  '15', 'orange'],
                        ['Días pendientes','15','green'],
                    ] as [$label, $val, $color])
                    <div class="bg-{{ $color }}-50 rounded-xl p-4 text-center border border-{{ $color }}-100">
                        <p class="text-xs text-{{ $color }}-500 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-2xl font-bold text-{{ $color }}-700 mt-1">{{ $val }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Historial de novedades --}}
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                                <th class="text-left px-4 py-3">Tipo</th>
                                <th class="text-left px-4 py-3">Descripción</th>
                                <th class="text-left px-4 py-3">Desde</th>
                                <th class="text-left px-4 py-3">Hasta</th>
                                <th class="text-left px-4 py-3">Días</th>
                                <th class="text-left px-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($novedades[$emp['id']] as $nov)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full
                                        @if($nov['tipo'] === 'Vacaciones')  bg-blue-100 text-blue-700
                                        @elseif($nov['tipo'] === 'Incapacidad') bg-red-100 text-red-700
                                        @elseif($nov['tipo'] === 'Permiso') bg-yellow-100 text-yellow-700
                                        @elseif($nov['tipo'] === 'Comisión') bg-purple-100 text-purple-700
                                        @else bg-gray-100 text-gray-600
                                        @endif">
                                        {{ $nov['tipo'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $nov['descripcion'] }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $nov['desde'] !== '—' ? \Carbon\Carbon::parse($nov['desde'])->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ ($nov['hasta'] !== '—' && $nov['hasta'] !== '') ? \Carbon\Carbon::parse($nov['hasta'])->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $nov['dias'] ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full
                                        @if($nov['estado'] === 'Aprobado')   bg-green-100 text-green-700
                                        @elseif($nov['estado'] === 'Pendiente') bg-yellow-100 text-yellow-700
                                        @else bg-gray-100 text-gray-600
                                        @endif">
                                        {{ $nov['estado'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

        </div>{{-- fin tabs --}}

    </div>{{-- fin ficha empleado --}}
    @endforeach

</div>{{-- fin layout principal --}}

@endsection
