@extends('layouts.app-sidebar')

@section('header', 'Asignar Horario')

@section('slot')
<div class="max-w-4xl mx-auto py-8 px-4">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Asignar Horario</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                <span class="font-medium text-gray-700">{{ $nombreDocente }}</span>
                &mdash; {{ $nombreMat }}
                <span class="ml-1 text-xs bg-gray-100 border border-gray-300 rounded px-2 py-0.5">Curso {{ $curso }}</span>
            </p>
        </div>
        <a href="{{ route('admin.asignaciones') }}?ver_asig={{ $docente }}#asig-individual"
           class="text-sm text-blue-700 hover:underline">← Volver</a>
    </div>

    {{-- Leyenda --}}
    <div class="flex flex-wrap gap-4 mb-5 text-xs text-gray-600">
        <span class="flex items-center gap-1.5">
            <span class="w-5 h-5 rounded bg-emerald-500 inline-block"></span> Ya asignado aquí
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-5 h-5 rounded bg-gray-100 border border-gray-300 inline-block"></span> Libre — clic para asignar
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-5 h-5 rounded bg-gray-300 inline-block"></span> Ocupado por otra materia
        </span>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden"
         x-data="asignarHorario({
             curso: '{{ $cursoBase }}',
             codMatHorario: {{ $codMatHorario }},
             estado: {{ json_encode($horariosCurso) }},
             nombreMat: {{ json_encode($nombreMat) }},
             materias: {{ json_encode($materiasNombres) }}
         })">

        <div class="px-5 py-3 bg-blue-800 text-white text-sm font-semibold">
            Horario del curso {{ $cursoBase }}
        </div>

        <div class="p-5 overflow-x-auto">
            <table class="text-xs border-collapse">
                <thead>
                    <tr>
                        <th class="w-20 pr-4 text-right text-gray-400 font-normal pb-2"></th>
                        @foreach($dias as $dNum => $dLabel)
                            <th class="text-center font-semibold text-gray-600 pb-2 px-1 min-w-[52px]">{{ $dLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($horas as $hNum => $hLabel)
                        <tr>
                            <td class="text-right pr-4 text-gray-400 py-1.5 whitespace-nowrap">{{ $hLabel }}</td>
                            @foreach($dias as $dNum => $dLabel)
                                <td class="px-1 py-1 text-center">
                                    <button
                                        type="button"
                                        @click="toggle({{ $dNum }}, {{ $hNum }})"
                                        :class="celdaClass({{ $dNum }}, {{ $hNum }})"
                                        :disabled="!esLibreOAsignado({{ $dNum }}, {{ $hNum }}) || guardando"
                                        :title="celdaTitle({{ $dNum }}, {{ $hNum }})"
                                        class="w-12 h-9 rounded text-xs font-bold transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-400 disabled:cursor-not-allowed">
                                        <span x-text="celdaLabel({{ $dNum }}, {{ $hNum }})"></span>
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div x-show="msg" x-transition
             class="px-5 py-2 text-xs text-center border-t"
             :class="err ? 'bg-red-50 text-red-600 border-red-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100'"
             x-text="msg">
        </div>
    </div>
</div>

<script>
function asignarHorario({ curso, codMatHorario, estado, nombreMat, materias }) {
    return {
        curso, codMatHorario, estado, nombreMat, materias,
        msg: '', err: false, guardando: false,

        actual(dia, hora) {
            return this.estado[dia]?.[hora] ?? null;
        },

        esLibreOAsignado(dia, hora) {
            const v = this.actual(dia, hora);
            return v === null || v === 0 || v === this.codMatHorario;
        },

        celdaClass(dia, hora) {
            const v = this.actual(dia, hora);
            if (v === this.codMatHorario)
                return 'bg-emerald-500 text-white cursor-default';
            if (v !== null && v !== 0)
                return 'bg-gray-300 text-gray-500 cursor-not-allowed';
            return 'bg-gray-100 border border-gray-300 text-gray-400 hover:bg-blue-100 hover:border-blue-400 hover:text-blue-700 cursor-pointer';
        },

        celdaTitle(dia, hora) {
            const v = this.actual(dia, hora);
            if (v === this.codMatHorario) return `${this.nombreMat} — ya asignado`;
            if (v !== null && v !== 0) return `Ocupado: ${this.materias[v] ?? 'mat.'+v}`;
            return `Libre — asignar ${this.nombreMat}`;
        },

        celdaLabel(dia, hora) {
            const v = this.actual(dia, hora);
            if (v === this.codMatHorario) return '✓';
            if (v !== null && v !== 0) return '·';
            return '';
        },

        async toggle(dia, hora) {
            const v = this.actual(dia, hora);
            if (v === this.codMatHorario) return; // ya asignado, no se quita
            if (v !== null && v !== 0) return;     // ocupado, no se toca

            this.guardando = true;
            try {
                const res = await fetch('{{ route('admin.asignaciones.horario.slot') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ curso: this.curso, dia, hora, codigo_mat_horario: this.codMatHorario })
                });
                const data = await res.json();
                if (data.ok) {
                    this.estado = data.estado;
                    this.msg = `Día ${dia} · Hora ${hora} — asignado.`;
                    this.err = false;
                } else {
                    this.msg = data.error ?? 'Error al guardar.';
                    this.err = true;
                }
            } catch {
                this.msg = 'Error de red.'; this.err = true;
            }
            this.guardando = false;
            setTimeout(() => this.msg = '', 3000);
        }
    };
}
</script>
@endsection
