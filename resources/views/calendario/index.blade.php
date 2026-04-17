@extends('layouts.app-sidebar')

@section('header', 'Calendario Académico')

@section('slot')
@php
    Carbon\Carbon::setLocale('es');
    $meses  = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $hoyStr = now()->toDateString();

    $mesPrev = $mes === 1 ? ['mes' => 12, 'anio' => $anio - 1] : ['mes' => $mes - 1, 'anio' => $anio];
    $mesSig  = $mes === 12 ? ['mes' => 1,  'anio' => $anio + 1] : ['mes' => $mes + 1, 'anio' => $anio];

    $primerDia = $inicio->dayOfWeekIso; // 1=Lun … 7=Dom
    $diasEnMes = $inicio->daysInMonth;

    $puedeEditar = in_array($profile, ['SuperAd', 'Admin']);

    // Colores de borde/fondo por visibilidad
    $visColors = [
        'todos'      => ['border' => '#16a34a', 'bg' => '#f0fdf4', 'badge_bg' => '#dcfce7', 'badge_text' => '#166534', 'label' => 'Todos'],
        'interno'    => ['border' => '#94a3b8', 'bg' => '#f8fafc', 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569', 'label' => 'Interno'],
        'docentes'   => ['border' => '#3b82f6', 'bg' => '#eff6ff', 'badge_bg' => '#dbeafe', 'badge_text' => '#1e40af', 'label' => 'Docentes'],
        'directivas' => ['border' => '#8b5cf6', 'bg' => '#f5f3ff', 'badge_bg' => '#ede9fe', 'badge_text' => '#5b21b6', 'label' => 'Directivas'],
        'padres'     => ['border' => '#f97316', 'bg' => '#fff7ed', 'badge_bg' => '#ffedd5', 'badge_text' => '#9a3412', 'label' => 'Padres'],
    ];
@endphp

<style>
/* ── Reset base ── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Tarjetas de estado ── */
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

/* ── Banner evento hoy ── */
.hoy-banner {
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
    padding: 10px 16px; display: flex; align-items: flex-start; gap: 10px;
    font-size: .82rem; color: #92400e;
}

/* ── Cuerpo: grilla + panel ── */
.cal-body { display: flex; gap: 16px; align-items: flex-start; }
@media (max-width: 767px) { .cal-body { display: none; } }

/* ── Vista lista móvil ── */
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
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    border-left: 4px solid transparent;
}
.cal-list-item.hoy        { background: linear-gradient(135deg,#1d4ed8,#3b82f6); border-left-color: #1d4ed8; }
.cal-list-item.nohabil    { opacity: .45; box-shadow: none; }
.cal-list-item.con-evento { /* border-left ya viene inline */ }

.cli-fecha {
    min-width: 46px; text-align: center; flex-shrink: 0;
}
.cli-num {
    font-size: 1.3rem; font-weight: 900; line-height: 1; color: #1e3a8a;
}
.cal-list-item.hoy .cli-num { color: #fff; }
.cal-list-item.nohabil .cli-num { color: #94a3b8; }
.cli-dow {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #94a3b8; margin-top: 2px;
}
.cal-list-item.hoy .cli-dow { color: #bfdbfe; }

.cli-sep { width: 1px; height: 36px; background: #e2e8f0; flex-shrink: 0; }
.cal-list-item.hoy .cli-sep { background: rgba(255,255,255,.25); }

.cli-info { flex: 1 1 0; min-width: 0; }
.cli-dia {
    font-size: 10px; font-weight: 700; color: #3b82f6;
    letter-spacing: .03em; line-height: 1; margin-bottom: 2px;
}
.cal-list-item.hoy .cli-dia { color: #bfdbfe; }
.cli-evento {
    font-size: .8rem; font-weight: 600; color: #1e293b;
    line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.cal-list-item.hoy .cli-evento { color: #fff; }
.cli-badge {
    display: inline-block; font-size: 9px; font-weight: 700;
    padding: 1px 6px; border-radius: 999px; margin-top: 3px;
}
.cli-edit-btn {
    flex-shrink: 0; background: none; border: 1px solid #e2e8f0;
    border-radius: 7px; padding: 5px 8px; font-size: 12px;
    cursor: pointer; color: #94a3b8; transition: all .15s;
}
.cli-edit-btn:hover { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.cal-list-item.hoy .cli-edit-btn { border-color: rgba(255,255,255,.3); color: rgba(255,255,255,.7); }
.cal-list-item.hoy .cli-edit-btn:hover { background: rgba(255,255,255,.15); color: #fff; }

/* ── Tarjeta grilla ── */
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

/* ── Tabla del calendario ── */
.cal-table { width: 100%; border-collapse: separate; border-spacing: 4px; table-layout: fixed; }
.cal-table th {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #94a3b8; text-align: center; padding: 4px 0 8px;
    width: calc(100% / 7);
}
.cal-table td { padding: 0; vertical-align: top; width: calc(100% / 7); }

.cal-cell {
    height: 84px; border-radius: 10px; padding: 6px 7px 5px;
    display: flex; flex-direction: column; gap: 2px;
    transition: box-shadow .15s, transform .1s;
    border: 1.5px solid transparent;
    position: relative; overflow: hidden;
}
.cal-cell-habil {
    background: #f8fafc; border-color: #e2e8f0; cursor: default;
}
.cal-cell-habil:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.cal-cell-editable { cursor: pointer; }
.cal-cell-editable:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,.12); }
.cal-cell-hoy {
    background: linear-gradient(150deg, #1d4ed8, #2563eb) !important;
    border-color: #1d4ed8 !important;
}
.cal-cell-nohabil { height: 84px; border-radius: 10px; }
.cal-cell-empty   { height: 84px; }

/* Número de día */
.cell-num {
    font-size: .9rem; font-weight: 800; line-height: 1; color: #334155;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.cal-cell-hoy .cell-num { color: #fff; }
.cal-cell-nohabil .cell-num { color: #cbd5e1; font-weight: 600; }

.cell-dia-label {
    font-size: 9px; font-weight: 700; color: #3b82f6; letter-spacing: .03em; line-height: 1;
    flex-shrink: 0;
}
.cal-cell-hoy .cell-dia-label { color: #bfdbfe; }

/* Chip de evento en la celda */
.cell-evento {
    font-size: 9px; font-weight: 600; line-height: 1.3;
    padding: 2px 5px; border-radius: 5px; margin-top: 1px;
    overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
    word-break: break-word; flex: 1 1 0; min-height: 0;
}
.cal-cell-hoy .cell-evento { background: rgba(255,255,255,.18); color: #fff; }

/* Ícono editar (esquina) */
.cell-edit-icon {
    font-size: 9px; opacity: 0; transition: opacity .15s; color: #94a3b8; flex-shrink: 0;
}
.cal-cell-editable:hover .cell-edit-icon { opacity: 1; }

/* ── Leyenda ── */
.cal-leyenda {
    display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px;
    padding-top: 12px; border-top: 1px solid #f1f5f9;
    font-size: 11px; color: #64748b;
}
.cal-leyenda span { display: flex; align-items: center; gap: 5px; }
.leg-sq  { width: 12px; height: 12px; border-radius: 4px; display: inline-block; border: 1.5px solid; }
.leg-dot { width: 8px;  height: 8px;  border-radius: 50%; display: inline-block; }

/* ── Panel lateral ── */
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
.ev-item { border-left: 3px solid #e2e8f0; padding-left: 10px; cursor: default; }
.ev-item.editable { cursor: pointer; }
.ev-item.editable:hover .ev-text { color: #1d4ed8; }
.ev-date { font-size: 10px; color: #94a3b8; margin-bottom: 1px; }
.ev-text { font-size: .78rem; color: #1e293b; line-height: 1.35; }
.ev-badge {
    display: inline-block; font-size: 9px; font-weight: 700;
    padding: 1px 6px; border-radius: 999px; margin-top: 3px;
}

/* ── Modal ── */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 9000;
    display: none; align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: #fff; border-radius: 18px; padding: 28px 28px 22px;
    width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,.2);
    animation: modalIn .18s ease-out;
}
@keyframes modalIn { from { opacity:0; transform:scale(.95) } to { opacity:1; transform:scale(1) } }
.modal-title { font-size: 1rem; font-weight: 700; color: #1e3a8a; margin-bottom: 4px; }
.modal-sub   { font-size: .78rem; color: #94a3b8; margin-bottom: 18px; }

.form-label { font-size: .75rem; font-weight: 700; color: #475569; margin-bottom: 5px; display: block; }
.form-input, .form-select, .form-textarea {
    width: 100%; border: 1.5px solid #e2e8f0; border-radius: 9px;
    padding: 9px 12px; font-size: .85rem; color: #1e293b;
    outline: none; transition: border-color .15s, box-shadow .15s;
    background: #fff; font-family: inherit;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15);
}
.form-textarea { resize: vertical; min-height: 80px; }

.vis-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
.vis-btn {
    padding: 7px 4px; border-radius: 8px; border: 2px solid #e2e8f0;
    font-size: 10px; font-weight: 700; text-align: center; cursor: pointer;
    transition: all .15s; background: #fff; color: #64748b;
}
.vis-btn.selected { border-color: currentColor; }

.modal-actions { display: flex; gap: 8px; margin-top: 20px; }
.btn-save {
    flex: 1; background: #1d4ed8; color: #fff; border: none;
    border-radius: 9px; padding: 10px; font-size: .85rem; font-weight: 700;
    cursor: pointer; transition: background .15s;
}
.btn-save:hover { background: #1e40af; }
.btn-save:disabled { background: #93c5fd; cursor: not-allowed; }
.btn-delete {
    background: #fee2e2; color: #b91c1c; border: none; border-radius: 9px;
    padding: 10px 16px; font-size: .85rem; font-weight: 700; cursor: pointer;
    transition: background .15s;
}
.btn-delete:hover { background: #fecaca; }
.btn-delete.hidden { display: none; }
.btn-cancel {
    background: #f1f5f9; color: #64748b; border: none; border-radius: 9px;
    padding: 10px 16px; font-size: .85rem; font-weight: 700; cursor: pointer;
}
.btn-cancel:hover { background: #e2e8f0; }

.modal-alert { font-size: .78rem; color: #dc2626; margin-top: 8px; display: none; }

/* ── Eventos en modal ── */
.ev-item-modal {
    border-radius: 8px; padding: 8px 10px; margin-bottom: 6px;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;
}
.ev-item-content { flex: 1 1 0; min-width: 0; }
.ev-item-texto { font-size: .82rem; color: #1e293b; line-height: 1.4; display: block; word-break: break-word; }
.ev-badge-sm { display: inline-block; font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 999px; margin-top: 3px; }
.ev-item-btns { display: flex; gap: 4px; flex-shrink: 0; margin-top: 1px; }
.ev-btn-edit, .ev-btn-del {
    background: none; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 2px 7px; font-size: 11px; cursor: pointer; line-height: 1.6; transition: all .12s;
}
.ev-btn-edit:hover { background: #eff6ff; border-color: #bfdbfe; }
.ev-btn-del:hover  { background: #fee2e2; border-color: #fecaca; }

/* Vis mini (edición inline) */
.vis-grid-mini { display: flex; flex-wrap: wrap; gap: 4px; margin: 6px 0 8px; }
.vis-btn-mini {
    padding: 4px 9px; border-radius: 6px; border: 1.5px solid #e2e8f0;
    font-size: 9px; font-weight: 700; cursor: pointer; transition: all .12s; background: #fff; color: #64748b;
}
.btn-save-sm {
    background: #1d4ed8; color: #fff; border: none; border-radius: 7px;
    padding: 6px 14px; font-size: .78rem; font-weight: 700; cursor: pointer;
}
.btn-save-sm:hover { background: #1e40af; }
.btn-cancel-sm {
    background: #f1f5f9; color: #64748b; border: none; border-radius: 7px;
    padding: 6px 14px; font-size: .78rem; font-weight: 700; cursor: pointer;
}
.btn-cancel-sm:hover { background: #e2e8f0; }
.modal-sep { border: none; border-top: 1px solid #f1f5f9; margin: 14px 0; }
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

    {{-- Eventos de hoy --}}
    @if($eventosHoy->isNotEmpty())
    <div style="display:flex;flex-direction:column;gap:6px;">
    @foreach($eventosHoy as $eventoHoy)
    @php $vc = $visColors[$eventoHoy->visibilidad] ?? $visColors['interno']; @endphp
    <div class="hoy-banner" style="border-color:{{ $vc['border'] }}; background:{{ $vc['bg'] }}; color:{{ $vc['badge_text'] }};">
        <span style="margin-top:1px;">📌</span>
        <div>
            <strong>Hoy:</strong> {{ $eventoHoy->evento }}
            <span class="ev-badge" style="background:{{ $vc['badge_bg'] }};color:{{ $vc['badge_text'] }};margin-left:6px;">
                {{ $vc['label'] }}
            </span>
        </div>
    </div>
    @endforeach
    </div>
    @endif

    {{-- ── Vista lista (móvil) ── --}}
    <div class="cal-mobile">

        {{-- Navegación de mes --}}
        <div class="cal-mobile-nav">
            <a href="{{ route('calendario.index', $mesPrev) }}" class="cal-nav-btn">‹</a>
            <span class="cal-mobile-nav-title">{{ $meses[$mes - 1] }} {{ $anio }}</span>
            <a href="{{ route('calendario.index', $mesSig) }}" class="cal-nav-btn">›</a>
        </div>

        {{-- Lista de días --}}
        <div class="cal-list">
        @php
            $diasSemana = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        @endphp
        @for($d = 1; $d <= $diasEnMes; $d++)
        @php
            $fechaStr      = \Carbon\Carbon::create($anio, $mes, $d)->toDateString();
            $entrada       = $diasMes[$fechaStr] ?? null;
            $esHoy         = $fechaStr === $hoyStr;
            $esDiaHabil    = $entrada && $entrada->dia_ciclo > 0;
            $eventosDelDia = $eventosPorFecha[$fechaStr] ?? collect();
            $tieneEvento   = $eventosDelDia->isNotEmpty();
            $primerEvento  = $tieneEvento ? $eventosDelDia->first() : null;
            $vc            = $tieneEvento ? ($visColors[$primerEvento->visibilidad] ?? $visColors['interno']) : null;
            $dow           = \Carbon\Carbon::create($anio, $mes, $d)->dayOfWeekIso;

            $itemClass = 'cal-list-item';
            if ($esHoy)           $itemClass .= ' hoy';
            elseif (!$esDiaHabil) $itemClass .= ' nohabil';
            elseif ($tieneEvento) $itemClass .= ' con-evento';

            $eventosJson = json_encode($eventosDelDia->values()->toArray());
        @endphp
        <div class="{{ $itemClass }}"
            @if($tieneEvento && !$esHoy) style="border-left-color:{{ $vc['border'] }}; background:{{ $vc['bg'] }};" @endif
            @if($puedeEditar)
                onclick="abrirModal('{{ $fechaStr }}', {{ $d }}, {{ $eventosJson }}, {{ $esDiaHabil ? 'true' : 'false' }})"
                style="{{ ($tieneEvento && !$esHoy) ? 'border-left-color:'.$vc['border'].'; background:'.$vc['bg'].';' : '' }} cursor:pointer;"
            @endif
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
                    @foreach($eventosDelDia as $evItem)
                    @php $vcEv = $visColors[$evItem->visibilidad] ?? $visColors['interno']; @endphp
                    <div class="cli-evento" style="{{ !$esHoy ? 'color:#1e293b;' : '' }}">{{ $evItem->evento }}</div>
                    @if(!$esHoy)
                    <span class="cli-badge" style="background:{{ $vcEv['badge_bg'] }};color:{{ $vcEv['badge_text'] }};">
                        {{ $vcEv['label'] }}
                    </span>
                    @endif
                    @endforeach
                @elseif(!$esDiaHabil)
                    <div style="font-size:.75rem;color:#94a3b8;">No académico</div>
                @else
                    <div style="font-size:.75rem;color:#cbd5e1;font-style:italic;">Sin evento</div>
                @endif
            </div>

            @if($puedeEditar)
            <button class="cli-edit-btn"
                onclick="event.stopPropagation(); abrirModal('{{ $fechaStr }}', {{ $d }}, {{ $eventosJson }}, {{ $esDiaHabil ? 'true' : 'false' }})">
                ✏️
            </button>
            @endif
        </div>
        @endfor
        </div>

    </div>

    {{-- ── Cuerpo (escritorio) ── --}}
    <div class="cal-body">

        {{-- Grilla del mes --}}
        <div class="cal-card">
            <div class="cal-nav">
                <a href="{{ route('calendario.index', $mesPrev) }}" class="cal-nav-btn">‹</a>
                <span class="cal-nav-title">{{ $meses[$mes - 1] }} {{ $anio }}</span>
                <a href="{{ route('calendario.index', $mesSig) }}" class="cal-nav-btn">›</a>
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
                    $fechaStr      = \Carbon\Carbon::create($anio, $mes, $d)->toDateString();
                    $entrada       = $diasMes[$fechaStr] ?? null;
                    $esHoy         = $fechaStr === $hoyStr;
                    $esDiaHabil    = $entrada && $entrada->dia_ciclo > 0;
                    $eventosDelDia = $eventosPorFecha[$fechaStr] ?? collect();
                    $tieneEvento   = $eventosDelDia->isNotEmpty();
                    $primerEvento  = $tieneEvento ? $eventosDelDia->first() : null;
                    $vc            = $tieneEvento ? ($visColors[$primerEvento->visibilidad] ?? $visColors['interno']) : null;
                    $eventosJson   = json_encode($eventosDelDia->values()->toArray());

                    $cellClass = 'cal-cell';
                    if ($esHoy) {
                        $cellClass .= ' cal-cell-hoy';
                    } elseif ($esDiaHabil) {
                        $cellClass .= ' cal-cell-habil';
                        if ($puedeEditar) $cellClass .= ' cal-cell-editable';
                    } else {
                        $cellClass .= ' cal-cell-nohabil';
                        if ($puedeEditar) $cellClass .= ' cal-cell-editable';
                    }
                @endphp
                <td>
                    <div class="{{ $cellClass }}"
                        @if($tieneEvento && !$esHoy) style="border-color:{{ $vc['border'] }}; background:{{ $vc['bg'] }};" @endif
                        @if($puedeEditar)
                            onclick="abrirModal('{{ $fechaStr }}', {{ $d }}, {{ $eventosJson }}, {{ $esDiaHabil ? 'true' : 'false' }})"
                        @endif
                    >
                        <div class="cell-num">
                            <span>{{ $d }}</span>
                            @if($puedeEditar)
                            <span class="cell-edit-icon">✏️</span>
                            @endif
                        </div>

                        @if($esDiaHabil)
                        <div class="cell-dia-label">Día {{ $entrada->dia_ciclo }}</div>
                        @endif

                        @foreach($eventosDelDia->take(2) as $evCell)
                        @php $vcCell = $visColors[$evCell->visibilidad] ?? $visColors['interno']; @endphp
                        <div class="cell-evento"
                            @if(!$esHoy) style="background:{{ $vcCell['badge_bg'] }};color:{{ $vcCell['badge_text'] }};" @endif>
                            {{ $evCell->evento }}
                        </div>
                        @endforeach
                        @if($eventosDelDia->count() > 2)
                        <div style="font-size:8px;color:#94a3b8;margin-top:1px;">+{{ $eventosDelDia->count() - 2 }} más</div>
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
                <span><span class="leg-sq" style="background:#f5f3ff;border-color:#8b5cf6;"></span> Directivas</span>
                <span><span class="leg-sq" style="background:#fff7ed;border-color:#f97316;"></span> Padres</span>
                <span><span class="leg-sq" style="background:#f8fafc;border-color:#94a3b8;"></span> Interno</span>
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
                        <span class="ev-badge" style="background:{{ $vc['badge_bg'] }};color:{{ $vc['badge_text'] }};">
                            {{ $vc['label'] }}
                        </span>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</div>

@if($puedeEditar)
{{-- ── Modal de eventos ── --}}
<div class="modal-overlay" id="cal-modal" onclick="cerrarModal(event)">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-title" id="modal-titulo"></div>
        <div class="modal-sub" id="modal-sub" style="margin-bottom:14px;"></div>

        {{-- Lista de eventos existentes --}}
        <div id="eventos-lista"></div>

        <hr class="modal-sep">

        {{-- Formulario nuevo evento --}}
        <label class="form-label">Agregar evento</label>
        <textarea class="form-textarea" id="nuevo-texto" placeholder="Ej: Izada de bandera, Reunión de padres…" rows="2" style="margin-bottom:8px;"></textarea>

        <label class="form-label" style="margin-bottom:5px;">Visibilidad</label>
        <div class="vis-grid" id="vis-nuevo">
            @foreach(['todos'=>['Todos','#dcfce7','#166534','#16a34a'],'docentes'=>['Docentes','#dbeafe','#1e40af','#3b82f6'],'directivas'=>['Directivas','#ede9fe','#5b21b6','#8b5cf6'],'padres'=>['Padres','#ffedd5','#9a3412','#f97316'],'interno'=>['Interno','#f1f5f9','#475569','#94a3b8']] as $vk => $vv)
            <button type="button" class="vis-btn" data-vis="{{ $vk }}"
                style="color:{{ $vv[2] }};"
                onclick="selVis('nuevo','{{ $vk }}')">{{ $vv[0] }}</button>
            @endforeach
        </div>

        <div class="modal-alert" id="modal-alert">Por favor escribe un evento antes de agregar.</div>

        <div class="modal-actions" style="margin-top:16px;">
            <button class="btn-save" id="btn-agregar" onclick="crearEvento()">+ Agregar evento</button>
            <button class="btn-cancel" onclick="cerrarModal()">Cerrar</button>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const VIS_STYLES = {
    todos:      { bg: '#dcfce7', text: '#166534', border: '#16a34a', label: 'Todos' },
    docentes:   { bg: '#dbeafe', text: '#1e40af', border: '#3b82f6', label: 'Docentes' },
    directivas: { bg: '#ede9fe', text: '#5b21b6', border: '#8b5cf6', label: 'Directivas' },
    padres:     { bg: '#ffedd5', text: '#9a3412', border: '#f97316', label: 'Padres' },
    interno:    { bg: '#f1f5f9', text: '#475569', border: '#94a3b8', label: 'Interno' },
};

let modalFecha   = null;
let modalEventos = [];
let visNuevo     = 'interno';
let visEdicion   = 'interno';

function abrirModal(fecha, dia, eventos, esDiaHabil) {
    modalFecha   = fecha;
    modalEventos = eventos || [];

    const [anio, mes, d] = fecha.split('-');
    const meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
                   'agosto','septiembre','octubre','noviembre','diciembre'];
    document.getElementById('modal-titulo').textContent =
        `${parseInt(d)} de ${meses[parseInt(mes)-1]} de ${anio}`;
    document.getElementById('modal-sub').textContent = esDiaHabil ? 'Día académico' : '';

    renderLista(modalEventos);

    document.getElementById('nuevo-texto').value = '';
    selVis('nuevo', 'interno');
    document.getElementById('modal-alert').style.display = 'none';

    document.getElementById('cal-modal').classList.add('open');
    setTimeout(() => document.getElementById('nuevo-texto').focus(), 100);
}

function renderLista(eventos) {
    const lista = document.getElementById('eventos-lista');
    if (!eventos || eventos.length === 0) {
        lista.innerHTML = '<p style="font-size:.78rem;color:#94a3b8;font-style:italic;margin-bottom:4px;">Sin eventos para este día.</p>';
        return;
    }
    lista.innerHTML = eventos.map(ev => itemHTML(ev)).join('');
}

function itemHTML(ev) {
    const s = VIS_STYLES[ev.visibilidad] || VIS_STYLES.interno;
    return `<div class="ev-item-modal" id="ev-${ev.id}" style="border-left:3px solid ${s.border};background:${s.bg};">
        <div class="ev-item-content">
            <span class="ev-item-texto">${escH(ev.evento)}</span>
            <span class="ev-badge-sm" style="background:${s.bg};color:${s.text};">${s.label}</span>
        </div>
        <div class="ev-item-btns">
            <button class="ev-btn-edit" title="Editar" onclick="iniciarEdicion(${ev.id})">✏️</button>
            <button class="ev-btn-del"  title="Eliminar" onclick="eliminarEvento(${ev.id})">🗑️</button>
        </div>
    </div>`;
}

function iniciarEdicion(id) {
    const ev = modalEventos.find(e => e.id === id);
    if (!ev) return;
    visEdicion = ev.visibilidad;
    document.getElementById(`ev-${id}`).outerHTML = `
        <div id="ev-${id}" style="margin-bottom:6px;">
            <textarea class="form-textarea" id="edit-txt-${id}" rows="2" style="margin-bottom:6px;">${escH(ev.evento)}</textarea>
            <div class="vis-grid-mini" id="vis-edit-${id}">
                ${visGridMini('edit-' + id, visEdicion)}
            </div>
            <div style="display:flex;gap:6px;">
                <button class="btn-save-sm" onclick="guardarEdicion(${id})">Guardar</button>
                <button class="btn-cancel-sm" onclick="cancelarEdicion(${id})">Cancelar</button>
            </div>
        </div>`;
}

function cancelarEdicion(id) {
    const ev = modalEventos.find(e => e.id === id);
    if (!ev) { location.reload(); return; }
    document.getElementById(`ev-${id}`).outerHTML = itemHTML(ev);
}

function visGridMini(prefix, seleccionada) {
    return Object.entries(VIS_STYLES).map(([k, s]) => {
        const sel = k === seleccionada;
        const style = sel
            ? `background:${s.bg};border-color:${s.border};color:${s.text};`
            : `background:#fff;border-color:#e2e8f0;color:#64748b;`;
        return `<button type="button" class="vis-btn-mini" data-vis="${k}" style="${style}"
            onclick="selVis('${prefix}','${k}')">${s.label}</button>`;
    }).join('');
}

function selVis(prefix, vis) {
    const container = document.getElementById(`vis-${prefix}`);
    if (!container) return;
    container.querySelectorAll('[data-vis]').forEach(btn => {
        const k = btn.dataset.vis;
        const s = VIS_STYLES[k];
        if (k === vis) {
            btn.style.background  = s.bg;
            btn.style.borderColor = s.border;
            btn.style.color       = s.text;
        } else {
            btn.style.background  = '#fff';
            btn.style.borderColor = '#e2e8f0';
            btn.style.color       = '#64748b';
        }
    });
    if (prefix === 'nuevo') visNuevo = vis;
    else visEdicion = vis;
}

async function guardarEdicion(id) {
    const texto = document.getElementById(`edit-txt-${id}`).value.trim();
    if (!texto) return;
    try {
        const r = await fetch(`/calendario/evento/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ evento: texto, visibilidad: visEdicion }),
        });
        if (!r.ok) throw new Error();
        location.reload();
    } catch { alert('Error al guardar.'); }
}

async function eliminarEvento(id) {
    if (!confirm('¿Eliminar este evento?')) return;
    try {
        const r = await fetch(`/calendario/evento/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF },
        });
        if (!r.ok) throw new Error();
        location.reload();
    } catch { alert('Error al eliminar.'); }
}

async function crearEvento() {
    const texto  = document.getElementById('nuevo-texto').value.trim();
    const alerta = document.getElementById('modal-alert');
    if (!texto) { alerta.style.display = 'block'; return; }
    alerta.style.display = 'none';

    const btn = document.getElementById('btn-agregar');
    btn.disabled = true; btn.textContent = 'Agregando…';

    try {
        const r = await fetch(`/calendario/${modalFecha}/eventos`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ evento: texto, visibilidad: visNuevo }),
        });
        if (!r.ok) throw new Error();
        location.reload();
    } catch {
        btn.disabled = false; btn.textContent = '+ Agregar evento';
        alerta.textContent = 'Error al agregar. Intenta de nuevo.';
        alerta.style.display = 'block';
    }
}

function cerrarModal(e) {
    if (e && e.target !== document.getElementById('cal-modal')) return;
    document.getElementById('cal-modal').classList.remove('open');
    modalFecha = null;
}

function escH(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.getElementById('cal-modal').classList.remove('open');
});
</script>
@endif

@endsection
