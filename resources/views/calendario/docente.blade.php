@extends('layouts.app-sidebar')

@section('header', 'Calendario Académico')

@section('slot')
@php
    Carbon\Carbon::setLocale('es');
    $meses   = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $hoyStr  = now()->toDateString();

    $mesPrev = $mes === 1 ? ['mes' => 12, 'anio' => $anio - 1] : ['mes' => $mes - 1, 'anio' => $anio];
    $mesSig  = $mes === 12 ? ['mes' => 1,  'anio' => $anio + 1] : ['mes' => $mes + 1, 'anio' => $anio];

    $primerDia = $inicio->dayOfWeekIso;
    $diasEnMes = $inicio->daysInMonth;

    $visColors = [
        'todos'      => ['border' => '#16a34a', 'bg' => '#f0fdf4', 'badge_bg' => '#dcfce7', 'badge_text' => '#166534', 'label' => 'Todos'],
        'interno'    => ['border' => '#94a3b8', 'bg' => '#f8fafc', 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569', 'label' => 'Interno'],
        'docentes'   => ['border' => '#3b82f6', 'bg' => '#eff6ff', 'badge_bg' => '#dbeafe', 'badge_text' => '#1e40af', 'label' => 'Docentes'],
        'directivas' => ['border' => '#8b5cf6', 'bg' => '#f5f3ff', 'badge_bg' => '#ede9fe', 'badge_text' => '#5b21b6', 'label' => 'Directivas'],
        'padres'     => ['border' => '#f97316', 'bg' => '#fff7ed', 'badge_bg' => '#ffedd5', 'badge_text' => '#9a3412', 'label' => 'Padres'],
    ];
@endphp

<style>
*, *::before, *::after { box-sizing: border-box; }

.cal-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
@media (max-width: 767px) { .cal-stats { grid-template-columns: repeat(2, 1fr); gap: 8px; } }
.cal-stat {
    background: #fff; border-radius: 14px; padding: 14px 18px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07); display: flex; align-items: center; gap: 14px;
}
.cal-stat-hoy { background: linear-gradient(135deg, #1d4ed8, #3b82f6); color: #fff; }
.s-icon { font-size: 1.8rem; font-weight: 900; line-height: 1; min-width: 36px; text-align: center; }
.s-label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; opacity: .6; margin-bottom: 2px; }
.s-val   { font-size: .88rem; font-weight: 700; line-height: 1.2; }
.s-sub   { font-size: 10px; opacity: .55; margin-top: 2px; }
.cal-stat-hoy .s-label, .cal-stat-hoy .s-sub { color: #bfdbfe; }

.hoy-banner {
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
    padding: 10px 16px; display: flex; align-items: flex-start; gap: 10px;
    font-size: .82rem; color: #92400e;
}

.cal-body { display: flex; gap: 16px; align-items: flex-start; }
@media (max-width: 767px) { .cal-body { display: none; } }

.cal-mobile { display: none; }
@media (max-width: 767px) { .cal-mobile { display: flex; flex-direction: column; gap: 10px; } }
.cal-mobile-nav {
    background: #fff; border-radius: 14px; box-shadow: 0 1px 6px rgba(0,0,0,.08);
    padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;
}
.cal-mobile-nav-title { font-size: 1rem; font-weight: 700; color: #1e3a8a; }
.cal-list { display: flex; flex-direction: column; gap: 6px; }
.cal-list-item {
    background: #fff; border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06); border-left: 4px solid transparent;
}
.cal-list-item.hoy     { background: linear-gradient(135deg,#1d4ed8,#3b82f6); border-left-color: #1d4ed8; }
.cal-list-item.nohabil { opacity: .45; box-shadow: none; }
.cli-fecha { min-width: 46px; text-align: center; flex-shrink: 0; }
.cli-num   { font-size: 1.3rem; font-weight: 900; line-height: 1; color: #1e3a8a; }
.cal-list-item.hoy .cli-num { color: #fff; }
.cal-list-item.nohabil .cli-num { color: #94a3b8; }
.cli-dow { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-top: 2px; }
.cal-list-item.hoy .cli-dow { color: #bfdbfe; }
.cli-sep { width: 1px; height: 36px; background: #e2e8f0; flex-shrink: 0; }
.cal-list-item.hoy .cli-sep { background: rgba(255,255,255,.25); }
.cli-info { flex: 1 1 0; min-width: 0; }
.cli-dia { font-size: 10px; font-weight: 700; color: #3b82f6; letter-spacing: .03em; line-height: 1; margin-bottom: 2px; }
.cal-list-item.hoy .cli-dia { color: #bfdbfe; }
.cli-evento { font-size: .8rem; font-weight: 600; color: #1e293b; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-list-item.hoy .cli-evento { color: #fff; }
.cli-badge { display: inline-block; font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 999px; margin-top: 3px; }

.cal-card {
    background: #fff; border-radius: 16px; box-shadow: 0 1px 6px rgba(0,0,0,.08);
    padding: 20px 22px; flex: 1 1 0; min-width: 0;
}
.cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.cal-nav-btn {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px; font-size: 1.2rem; font-weight: 700;
    color: #94a3b8; text-decoration: none; transition: background .15s, color .15s;
}
.cal-nav-btn:hover { background: #eff6ff; color: #1d4ed8; }
.cal-nav-title { font-size: 1rem; font-weight: 700; color: #1e3a8a; }

.cal-table { width: 100%; border-collapse: separate; border-spacing: 4px; table-layout: fixed; }
.cal-table th {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #94a3b8; text-align: center; padding: 4px 0 8px;
}
.cal-table td { padding: 0; vertical-align: top; }
.cal-cell {
    height: 84px; border-radius: 10px; padding: 6px 7px 5px;
    display: flex; flex-direction: column; gap: 2px;
    border: 1.5px solid transparent; position: relative; overflow: hidden;
}
.cal-cell-habil   { background: #f8fafc; border-color: #e2e8f0; }
.cal-cell-hoy     { background: linear-gradient(150deg, #1d4ed8, #2563eb) !important; border-color: #1d4ed8 !important; }
.cal-cell-nohabil { height: 84px; border-radius: 10px; }
.cal-cell-empty   { height: 84px; }
.cell-num { font-size: .9rem; font-weight: 800; line-height: 1; color: #334155; flex-shrink: 0; }
.cal-cell-hoy .cell-num { color: #fff; }
.cal-cell-nohabil .cell-num { color: #cbd5e1; font-weight: 600; }
.cell-dia-label { font-size: 9px; font-weight: 700; color: #3b82f6; letter-spacing: .03em; line-height: 1; flex-shrink: 0; }
.cal-cell-hoy .cell-dia-label { color: #bfdbfe; }
.cell-evento {
    font-size: 9px; font-weight: 600; line-height: 1.3;
    padding: 2px 5px; border-radius: 5px; margin-top: 1px;
    overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
    word-break: break-word; flex: 1 1 0; min-height: 0;
}
.cal-cell-hoy .cell-evento { background: rgba(255,255,255,.18); color: #fff; }

.cal-leyenda {
    display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px;
    padding-top: 12px; border-top: 1px solid #f1f5f9;
    font-size: 11px; color: #64748b;
}
.cal-leyenda span { display: flex; align-items: center; gap: 5px; }
.leg-sq { width: 12px; height: 12px; border-radius: 4px; display: inline-block; border: 1.5px solid; }

.side-card {
    width: 256px; flex-shrink: 0; background: #fff; border-radius: 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.08); padding: 18px 18px 20px;
    display: flex; flex-direction: column; gap: 0;
}
.side-title { font-size: .82rem; font-weight: 700; color: #1e3a8a; margin-bottom: 12px; }
.ev-list {
    list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column;
    gap: 8px; max-height: 540px; overflow-y: auto; padding-right: 2px;
}
.ev-item { border-left: 3px solid #e2e8f0; padding-left: 10px; }
.ev-date { font-size: 10px; color: #94a3b8; margin-bottom: 1px; }
.ev-text { font-size: .78rem; color: #1e293b; line-height: 1.35; }
.ev-badge { display: inline-block; font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 999px; margin-top: 3px; }
</style>

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Barra de estado ── --}}
    <div class="cal-stats">
        <div class="cal-stat cal-stat-hoy">
            <div class="s-icon">{{ $hoy && $hoy->dia_ciclo > 0 ? $hoy->dia_ciclo : '—' }}</div>
            <div>
                <div class="s-label">Hoy</div>
                <div class="s-val">{{ $hoy && $hoy->dia_ciclo > 0 ? 'Día '.$hoy->dia_ciclo : 'No académico' }}</div>
                @if($hoy)<div class="s-sub">{{ \Carbon\Carbon::parse($hoy->fecha)->isoFormat('ddd D MMM') }}</div>@endif
            </div>
        </div>

        <div class="cal-stat">
            <div class="s-icon" style="color:#93c5fd; font-size:1.4rem;">{{ $manana ? $manana->dia_ciclo : '—' }}</div>
            <div>
                <div class="s-label">Próximo día hábil</div>
                <div class="s-val" style="color:#1e3a8a;">{{ $manana ? 'Día '.$manana->dia_ciclo : 'Sin datos' }}</div>
                @if($manana)<div class="s-sub">{{ \Carbon\Carbon::parse($manana->fecha)->isoFormat('ddd D MMM') }}</div>@endif
            </div>
        </div>

        <div class="cal-stat">
            <div style="min-width:36px;text-align:center;">
                <div style="font-size:1.5rem;font-weight:900;color:#1d4ed8;line-height:1;">{{ $infoCiclo['cicloEnPeriodo'] ?? '—' }}</div>
                <div style="font-size:10px;color:#94a3b8;">/ 7</div>
            </div>
            <div>
                <div class="s-label">Ciclo</div>
                @if($infoCiclo['ciclo'])
                    <div class="s-val" style="color:#1e3a8a;">Ciclo {{ $infoCiclo['cicloEnPeriodo'] }} de 7</div>
                    <div class="s-sub">Ciclo {{ $infoCiclo['ciclo'] }} del año</div>
                @else
                    <div class="s-val" style="color:#94a3b8;font-style:italic;">Sin datos</div>
                @endif
            </div>
        </div>

        <div class="cal-stat">
            <div style="min-width:36px;text-align:center;font-size:1.5rem;font-weight:900;color:#6d28d9;line-height:1;">{{ $infoCiclo['periodo'] ?? '—' }}</div>
            <div>
                <div class="s-label">Período</div>
                @if($infoCiclo['periodo'])
                    <div class="s-val" style="color:#1e3a8a;">Período {{ $infoCiclo['periodo'] }}</div>
                    <div class="s-sub">Ciclo {{ $infoCiclo['cicloEnPeriodo'] }} de 7</div>
                @else
                    <div class="s-val" style="color:#94a3b8;font-style:italic;">Sin datos</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Evento de hoy --}}
    @if($hoy && $hoy->evento)
    @php $vc = $visColors[$hoy->visibilidad] ?? $visColors['interno']; @endphp
    <div class="hoy-banner" style="border-color:{{ $vc['border'] }}; background:{{ $vc['bg'] }}; color:{{ $vc['badge_text'] }};">
        <span style="margin-top:1px;">📌</span>
        <div><strong>Hoy:</strong> {{ $hoy->evento }}</div>
    </div>
    @endif

    {{-- ── Vista lista (móvil) ── --}}
    <div class="cal-mobile">
        <div class="cal-mobile-nav">
            <a href="{{ route('calendario.docente', $mesPrev) }}" class="cal-nav-btn">‹</a>
            <span class="cal-mobile-nav-title">{{ $meses[$mes - 1] }} {{ $anio }}</span>
            <a href="{{ route('calendario.docente', $mesSig) }}" class="cal-nav-btn">›</a>
        </div>

        <div class="cal-list">
        @php $diasSemana = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']; @endphp
        @for($d = 1; $d <= $diasEnMes; $d++)
        @php
            $fechaStr   = \Carbon\Carbon::create($anio, $mes, $d)->toDateString();
            $entrada    = $diasMes[$fechaStr] ?? null;
            $esHoy      = $fechaStr === $hoyStr;
            $esDiaHabil = $entrada && $entrada->dia_ciclo > 0;
            $tieneEvento= $entrada && $entrada->evento;
            $vc         = $tieneEvento ? ($visColors[$entrada->visibilidad] ?? $visColors['interno']) : null;
            $dow        = \Carbon\Carbon::create($anio, $mes, $d)->dayOfWeekIso;
            $itemClass  = 'cal-list-item';
            if ($esHoy)           $itemClass .= ' hoy';
            elseif (!$esDiaHabil) $itemClass .= ' nohabil';
        @endphp
        <div class="{{ $itemClass }}"
            @if($tieneEvento && !$esHoy) style="border-left-color:{{ $vc['border'] }}; background:{{ $vc['bg'] }};" @endif
        >
            <div class="cli-fecha">
                <div class="cli-num">{{ $d }}</div>
                <div class="cli-dow">{{ $diasSemana[$dow] }}</div>
            </div>
            <div class="cli-sep"></div>
            <div class="cli-info">
                @if($esDiaHabil)
                    <div class="cli-dia">Día {{ $entrada->dia_ciclo }}</div>
                @endif
                @if($tieneEvento)
                    <div class="cli-evento">{{ $entrada->evento }}</div>
                @elseif(!$esDiaHabil)
                    <div style="font-size:.75rem;color:#94a3b8;">No académico</div>
                @else
                    <div style="font-size:.75rem;color:#cbd5e1;font-style:italic;">Sin evento</div>
                @endif
            </div>
        </div>
        @endfor
        </div>
    </div>

    {{-- ── Cuerpo (escritorio) ── --}}
    <div class="cal-body">

        {{-- Grilla del mes --}}
        <div class="cal-card">
            <div class="cal-nav">
                <a href="{{ route('calendario.docente', $mesPrev) }}" class="cal-nav-btn">‹</a>
                <span class="cal-nav-title">{{ $meses[$mes - 1] }} {{ $anio }}</span>
                <a href="{{ route('calendario.docente', $mesSig) }}" class="cal-nav-btn">›</a>
            </div>

            <table class="cal-table">
                <thead>
                    <tr>
                        @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $dk)
                        <th>{{ $dk }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @php
                    $col = 1;
                    echo '<tr>';
                    for ($i = 1; $i < $primerDia; $i++) {
                        echo '<td><div class="cal-cell cal-cell-empty"></div></td>';
                        $col++;
                    }
                @endphp

                @for($d = 1; $d <= $diasEnMes; $d++)
                @php
                    if ($col > 7) { echo '</tr><tr>'; $col = 1; }
                    $fechaStr    = \Carbon\Carbon::create($anio, $mes, $d)->toDateString();
                    $entrada     = $diasMes[$fechaStr] ?? null;
                    $esHoy       = $fechaStr === $hoyStr;
                    $tieneEvento = $entrada && $entrada->evento;
                    $esDiaHabil  = $entrada && $entrada->dia_ciclo > 0;
                    $vc          = $tieneEvento ? ($visColors[$entrada->visibilidad] ?? $visColors['interno']) : null;

                    $cellClass = 'cal-cell';
                    if ($esHoy)                $cellClass .= ' cal-cell-hoy';
                    elseif ($esDiaHabil)       $cellClass .= ' cal-cell-habil';
                    else                       $cellClass .= ' cal-cell-nohabil';
                @endphp
                <td>
                    <div class="{{ $cellClass }}"
                        @if($tieneEvento && !$esHoy) style="border-color:{{ $vc['border'] }}; background:{{ $vc['bg'] }};" @endif
                    >
                        <div class="cell-num">{{ $d }}</div>

                        @if($esDiaHabil)
                        <div class="cell-dia-label">Día {{ $entrada->dia_ciclo }}</div>
                        @endif

                        @if($tieneEvento)
                        <div class="cell-evento"
                            @if(!$esHoy) style="background:{{ $vc['badge_bg'] }};color:{{ $vc['badge_text'] }};" @endif>
                            {{ $entrada->evento }}
                        </div>
                        @endif
                    </div>
                </td>
                @php $col++; @endphp
                @endfor

                @php
                    while ($col <= 7) { echo '<td><div class="cal-cell cal-cell-empty"></div></td>'; $col++; }
                    echo '</tr>';
                @endphp
                </tbody>
            </table>

            <div class="cal-leyenda">
                <span><span class="leg-sq" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-color:#1d4ed8;"></span> Hoy</span>
                <span><span class="leg-sq" style="background:#f8fafc;border-color:#e2e8f0;"></span> Día hábil</span>
                <span><span class="leg-sq" style="background:#f0fdf4;border-color:#16a34a;"></span> Todos</span>
                <span><span class="leg-sq" style="background:#eff6ff;border-color:#3b82f6;"></span> Docentes</span>
            </div>
        </div>

        {{-- Panel lateral: próximos eventos --}}
        <div class="side-card">
            <div class="side-title">Próximos 30 días</div>

            @if($proximosEventos->isEmpty())
                <p style="font-size:.78rem;color:#94a3b8;font-style:italic;">Sin eventos próximos.</p>
            @else
                <ul class="ev-list">
                    @foreach($proximosEventos as $ev)
                    @php $vc = $visColors[$ev->visibilidad] ?? $visColors['interno']; @endphp
                    <li class="ev-item" style="border-left-color:{{ $vc['border'] }};">
                        <div class="ev-date">
                            {{ \Carbon\Carbon::parse($ev->fecha)->isoFormat('ddd D MMM') }}
                            @if($ev->dia_ciclo > 0)
                                · <span style="font-weight:700;color:#3b82f6;">Día {{ $ev->dia_ciclo }}</span>
                            @endif
                        </div>
                        <div class="ev-text">{{ $ev->evento }}</div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</div>
@endsection
