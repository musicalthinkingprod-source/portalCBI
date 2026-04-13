@extends('layouts.app-sidebar')

@section('header', 'Mi Horario')

@section('slot')
@php
    Carbon\Carbon::setLocale('es');

    // Horario real: inicio de cada hora
    $horaInicio = [
        1 => '7:00',  2 => '7:45',
        3 => '8:50',  4 => '9:35',
        5 => '10:20', 6 => '11:05',
        7 => '12:10', 8 => '12:55',
    ];
    // Fin de cada hora (45 min después)
    $horaFin = [
        1 => '7:45',  2 => '8:30',
        3 => '9:35',  4 => '10:20',
        5 => '11:05', 6 => '11:50',
        7 => '12:55', 8 => '13:40',
    ];

    // Bloques de dos horas y descansos que van después de un par
    $bloques   = [[1,2],[3,4],[5,6],[7,8]];
    $descansos = [2 => '8:30 – 8:50', 6 => '11:50 – 12:10'];

    // Pre-calcular por bloque y día si las dos horas son idénticas (→ merge)
    $mergeMatrix = [];
    foreach ($bloques as [$h1, $h2]) {
        foreach ($diasConDatos as $d) {
            $sig = fn($cells) => collect($cells)
                ->map(fn($c) => ($c['curso'] ?? '').'|'.($c['materia'] ?? ''))
                ->sort()->implode(',');
            $c1 = $grid[$h1][$d] ?? [];
            $c2 = $grid[$h2][$d] ?? [];
            $mergeMatrix[$h1][$d] = !empty($c1) && $sig($c1) === $sig($c2);
        }
    }
@endphp

<style>
*, *::before, *::after { box-sizing: border-box; }

.mh-header {
    background: linear-gradient(135deg, #4338ca, #6366f1);
    border-radius: 16px; padding: 18px 22px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 2px 8px rgba(99,102,241,.3);
}
.mh-avatar {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.2); display: flex;
    align-items: center; justify-content: center;
    font-size: 1.6rem; flex-shrink: 0;
}
.mh-name  { font-size: 1.1rem; font-weight: 800; color: #fff; line-height: 1.2; }
.mh-sub   { font-size: .78rem; color: #c7d2fe; margin-top: 2px; }

/* ── Grilla ── */
.grid-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.08); overflow: hidden;
}
.grid-scroll { overflow-x: auto; }

.hor-table { width: 100%; border-collapse: collapse; min-width: 480px; }
.hor-table th {
    background: #4338ca; color: #fff; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    padding: 10px 12px; text-align: center; white-space: nowrap;
}
.hor-table th.col-hora { text-align: left; width: 88px; }
.hor-table th.hoy-col  { background: #6366f1; }

.hor-table td {
    padding: 8px 10px; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle; text-align: center; height: 80px;
}
.hor-table td.col-hora {
    font-size: .78rem; font-weight: 700; color: #64748b;
    text-align: left; white-space: nowrap; background: #fafafa;
}
.hor-table tr:last-child td { border-bottom: none; }
.hor-table tr:hover td { background: #f8f7ff; }
.hor-table tr:hover td.col-hora { background: #f1f0ff; }

.cell-bloque {
    display: inline-flex; flex-direction: column; align-items: center;
    justify-content: center;
    gap: 2px; padding: 5px 8px; border-radius: 8px;
    background: #eef2ff; min-width: 80px; height: 56px; overflow: hidden;
}
.cell-bloque .curso-badge {
    font-size: 11px; font-weight: 800; color: #4338ca;
    background: #c7d2fe; border-radius: 5px; padding: 1px 7px;
}
.cell-bloque .mat-name {
    font-size: 10px; color: #4b5563; line-height: 1.3; text-align: center;
    overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}

/* Columna de hoy */
.hor-table td.hoy-col { background: #eef2ff; }
.hor-table tr:hover td.hoy-col { background: #e0e7ff; }

/* ── Secciones de reemplazos ── */
.section-title {
    font-size: .82rem; font-weight: 800; color: #374151;
    text-transform: uppercase; letter-spacing: .06em;
    margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
}
.section-title .badge-count {
    background: #e0e7ff; color: #4338ca; font-size: 10px;
    font-weight: 800; padding: 1px 7px; border-radius: 999px;
}

.reem-list { display: flex; flex-direction: column; gap: 8px; }
.reem-item {
    background: #fff; border-radius: 12px; padding: 12px 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    display: flex; align-items: center; gap: 14px;
    border-left: 4px solid #6366f1;
}
.reem-item.ausencia { border-left-color: #f97316; }

.reem-fecha {
    min-width: 56px; text-align: center; flex-shrink: 0;
}
.reem-fecha .day  { font-size: 1.3rem; font-weight: 900; color: #4338ca; line-height: 1; }
.reem-fecha .mon  { font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: .04em; }
.reem-item.ausencia .reem-fecha .day { color: #ea580c; }

.reem-sep { width: 1px; height: 36px; background: #e2e8f0; flex-shrink: 0; }

.reem-info { flex: 1 1 0; min-width: 0; }
.reem-hora-curso {
    font-size: .75rem; font-weight: 700; color: #4338ca; margin-bottom: 2px;
}
.reem-item.ausencia .reem-hora-curso { color: #ea580c; }
.reem-mat  { font-size: .82rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.reem-doc  { font-size: .73rem; color: #64748b; margin-top: 2px; }

.empty-msg {
    background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 10px;
    padding: 14px 18px; font-size: .8rem; color: #94a3b8; text-align: center;
    font-style: italic;
}
</style>

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Encabezado ── --}}
    <div class="mh-header">
        <div class="mh-avatar">👤</div>
        <div>
            <div class="mh-name">{{ $nombreDocente }}</div>
            <div class="mh-sub">
                @if($diaCicloHoy)
                    Hoy es Día {{ $diaCicloHoy }} del ciclo
                @else
                    Hoy no hay clases
                @endif
            </div>
        </div>
    </div>

    {{-- ── Grilla de horario ── --}}
    <div class="grid-card">
        <div style="padding:16px 20px 12px; border-bottom:1px solid #f1f5f9;">
            <div class="section-title">
                📅 Mi Horario
            </div>
        </div>

        @if(true)
        <div class="grid-scroll">
            <table class="hor-table">
                <thead>
                    <tr>
                        <th class="col-hora">Hora</th>
                        @foreach($diasConDatos as $diaNum)
                        @php
                            $esHoy     = $diaCicloHoy === $diaNum;
                            $proxFecha = $proximaFecha[$diaNum] ?? null;
                        @endphp
                        <th class="{{ $esHoy ? 'hoy-col' : '' }}">
                            {{ $dias[$diaNum] ?? 'Día '.$diaNum }}
                            @if($proxFecha)
                                <div style="font-size:9px; font-weight:400; opacity:.75; margin-top:2px;">
                                    {{ $proxFecha->isoFormat('ddd D MMM') }}
                                </div>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($bloques as [$h1, $h2])

                    {{-- ── Fila 1 del bloque ── --}}
                    <tr>
                        <td class="col-hora">
                            <span style="font-size:10px;font-weight:800;color:#4338ca;">{{ $h1 }}ª hora</span><br>
                            <span style="font-size:9px;font-weight:400;color:#94a3b8;">{{ $horaInicio[$h1] }} – {{ $horaFin[$h1] }}</span>
                        </td>
                        @foreach($diasConDatos as $diaNum)
                        @php
                            $esHoy   = $diaCicloHoy === $diaNum;
                            $merged  = $mergeMatrix[$h1][$diaNum] ?? false;
                            $celdas  = $grid[$h1][$diaNum] ?? [];
                        @endphp
                        <td @if($merged) rowspan="2" @endif
                            class="{{ $esHoy ? 'hoy-col' : '' }}"
                            @if($merged) style="vertical-align:middle;" @endif>
                            @if(!empty($celdas))
                                @foreach($celdas as $c)
                                <div class="cell-bloque">
                                    <span class="curso-badge">{{ $c['curso'] }}</span>
                                    <span class="mat-name">{{ $c['materia'] }}</span>
                                </div>
                                @endforeach
                            @else
                                <span style="color:#e2e8f0;font-size:.75rem;">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>

                    {{-- ── Fila 2 del bloque (siempre visible, celdas ya cubiertas por rowspan saltan) ── --}}
                    <tr>
                        <td class="col-hora">
                            <span style="font-size:10px;font-weight:800;color:#4338ca;">{{ $h2 }}ª hora</span><br>
                            <span style="font-size:9px;font-weight:400;color:#94a3b8;">{{ $horaInicio[$h2] }} – {{ $horaFin[$h2] }}</span>
                        </td>
                        @foreach($diasConDatos as $diaNum)
                        @if(!($mergeMatrix[$h1][$diaNum] ?? false))
                        @php
                            $esHoy  = $diaCicloHoy === $diaNum;
                            $celdas = $grid[$h2][$diaNum] ?? [];
                        @endphp
                        <td class="{{ $esHoy ? 'hoy-col' : '' }}">
                            @if(!empty($celdas))
                                @foreach($celdas as $c)
                                <div class="cell-bloque">
                                    <span class="curso-badge">{{ $c['curso'] }}</span>
                                    <span class="mat-name">{{ $c['materia'] }}</span>
                                </div>
                                @endforeach
                            @else
                                <span style="color:#e2e8f0;font-size:.75rem;">—</span>
                            @endif
                        </td>
                        @endif
                        @endforeach
                    </tr>

                    {{-- ── Fila de descanso (si aplica tras este par) ── --}}
                    @if(isset($descansos[$h2]))
                    <tr>
                        <td colspan="{{ count($diasConDatos) + 1 }}"
                            style="background:#fef9c3;text-align:center;padding:5px 10px;
                                   font-size:10px;font-weight:700;color:#92400e;letter-spacing:.05em;">
                            ☕ DESCANSO &nbsp;{{ $descansos[$h2] }}
                        </td>
                    </tr>
                    @endif

                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── Reemplazos a cubrir ── --}}
    <div>
        <div class="section-title">
            🔁 Reemplazos que debo cubrir
            <span class="badge-count">{{ $reemplazosACubrir->count() }}</span>
        </div>

        @if($reemplazosACubrir->isEmpty())
            <div class="empty-msg">No tienes reemplazos asignados en los próximos 30 días.</div>
        @else
            <div class="reem-list">
                @foreach($reemplazosACubrir as $r)
                @php
                    $fecha  = \Carbon\Carbon::parse($r->fecha);
                    $materia = $r->materia ?? 'Materia no identificada';
                @endphp
                <div class="reem-item">
                    <div class="reem-fecha">
                        <div class="day">{{ $fecha->day }}</div>
                        <div class="mon">{{ $fecha->isoFormat('MMM') }}</div>
                    </div>
                    <div class="reem-sep"></div>
                    <div class="reem-info">
                        <div class="reem-hora-curso">
                            {{ $horas[$r->hora] ?? 'Hora '.$r->hora }}
                            · Curso {{ $r->curso }}
                            @if($r->dia_ciclo) · Día {{ $r->dia_ciclo }} @endif
                        </div>
                        <div class="reem-mat">{{ $materia }}</div>
                        <div class="reem-doc">Reemplazando a: {{ $r->docente_ausente ?? 'Docente' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Mis ausencias con reemplazo ── --}}
    @if($misAusencias->isNotEmpty())
    <div>
        <div class="section-title">
            ⚠️ Mis ausencias con reemplazo asignado
            <span class="badge-count" style="background:#ffedd5;color:#9a3412;">{{ $misAusencias->count() }}</span>
        </div>

        <div class="reem-list">
            @foreach($misAusencias as $r)
            @php
                $fecha   = \Carbon\Carbon::parse($r->fecha);
                $materia = $r->materia ?? 'Materia no identificada';
            @endphp
            <div class="reem-item ausencia">
                <div class="reem-fecha">
                    <div class="day">{{ $fecha->day }}</div>
                    <div class="mon">{{ $fecha->isoFormat('MMM') }}</div>
                </div>
                <div class="reem-sep"></div>
                <div class="reem-info">
                    <div class="reem-hora-curso">
                        {{ $horas[$r->hora] ?? 'Hora '.$r->hora }}
                        · Curso {{ $r->curso }}
                        @if($r->dia_ciclo) · Día {{ $r->dia_ciclo }} @endif
                    </div>
                    <div class="reem-mat">{{ $materia }}</div>
                    <div class="reem-doc">Te reemplaza: {{ $r->docente_reemplazo ?? 'Sin asignar' }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
