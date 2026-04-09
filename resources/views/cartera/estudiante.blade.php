@extends('layouts.app-sidebar')

@section('header', 'Cartera · ' . ($estudiante ? trim("{$estudiante->NOMBRE1} {$estudiante->APELLIDO1}") : "Cód. {$codigo}"))

@section('slot')

@php $esReadOnly = auth()->user()->PROFILE === 'Contab'; @endphp

    <div class="mb-5">
        <a href="{{ route('cartera.deudores') }}" class="text-blue-700 hover:underline text-sm">← Volver a lista de deudores</a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
    @endif

    {{-- Encabezado del estudiante --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6 flex flex-wrap gap-6 items-start">
        <div class="flex-1 min-w-0">
            @if($estudiante)
                <h2 class="text-xl font-bold text-gray-800">
                    {{ trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2} {$estudiante->NOMBRE1} {$estudiante->NOMBRE2}") }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Código: {{ $codigo }} · Curso: {{ $estudiante->CURSO ?? '—' }}</p>
            @else
                <h2 class="text-xl font-bold text-gray-800">Código {{ $codigo }}</h2>
            @endif

            @if($infoPadres)
            <div class="mt-3 flex flex-wrap gap-3 text-xs">
                @php
                    $acudiente = $infoPadres->ACUD ?: ($infoPadres->MADRE ?: $infoPadres->PADRE);
                    $tels = collect([
                        'Acud.'  => $infoPadres->CEL_ACUD  ?: $infoPadres->TEL_ACUD,
                        'Madre'  => $infoPadres->CEL_MADRE ?: $infoPadres->TEL_MADRE,
                        'Padre'  => $infoPadres->CEL_PADRE ?: $infoPadres->TEL_PADRE,
                    ])->filter()->unique();
                @endphp
                @if($acudiente)
                    <span class="text-gray-600 font-medium">Acudiente: {{ $acudiente }}</span>
                @endif
                @foreach($tels as $label => $tel)
                    <a href="tel:{{ preg_replace('/\D/', '', $tel) }}"
                        class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold px-2 py-1 rounded-lg transition">
                        📞 {{ $label }}: {{ $tel }}
                    </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Resumen financiero --}}
        <div class="flex gap-4 flex-wrap">
            <div class="text-center bg-blue-50 rounded-xl px-5 py-3">
                <p class="text-xs text-blue-400 uppercase tracking-wide">Facturado</p>
                <p class="text-xl font-bold text-blue-800">$ {{ number_format($totalFacturado, 0, ',', '.') }}</p>
            </div>
            <div class="text-center bg-green-50 rounded-xl px-5 py-3">
                <p class="text-xs text-green-400 uppercase tracking-wide">Pagado</p>
                <p class="text-xl font-bold text-green-700">$ {{ number_format($totalPagado, 0, ',', '.') }}</p>
            </div>
            <div class="text-center {{ $saldo > 0 ? 'bg-red-50' : 'bg-gray-50' }} rounded-xl px-5 py-3">
                <p class="text-xs {{ $saldo > 0 ? 'text-red-400' : 'text-gray-400' }} uppercase tracking-wide">Saldo</p>
                <p class="text-xl font-bold {{ $saldo > 0 ? 'text-red-700' : 'text-gray-600' }}">$ {{ number_format($saldo, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Facturas --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-blue-800 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Facturación ({{ $facturas->count() }})</h3>
            </div>
            @if($facturas->isEmpty())
                <p class="px-5 py-4 text-sm text-gray-400">Sin registros de facturación.</p>
            @else
            <div class="overflow-x-auto max-h-72 overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left">Fecha</th>
                            <th class="px-3 py-2 text-left">Concepto</th>
                            <th class="px-3 py-2 text-left">Mes</th>
                            <th class="px-3 py-2 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($facturas as $f)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-gray-500">{{ $f->fecha }}</td>
                            <td class="px-3 py-2">{{ $f->concepto }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $f->mes }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-blue-800">$ {{ number_format($f->valor, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Pagos --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 bg-green-700 text-white">
                <h3 class="font-bold text-sm uppercase tracking-wide">Pagos ({{ $pagos->count() }})</h3>
            </div>
            @if($pagos->isEmpty())
                <p class="px-5 py-4 text-sm text-gray-400">Sin registros de pagos.</p>
            @else
            <div class="overflow-x-auto max-h-72 overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left">Fecha</th>
                            <th class="px-3 py-2 text-left">Concepto</th>
                            <th class="px-3 py-2 text-left">Mes</th>
                            <th class="px-3 py-2 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($pagos as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-gray-500">{{ $p->fecha }}</td>
                            <td class="px-3 py-2">{{ $p->concepto }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $p->mes }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-green-700">$ {{ number_format($p->valor, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>

    {{-- Módulo de Seguimiento Cartera --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 bg-gray-700 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm uppercase tracking-wide">Seguimiento Cartera</h3>
                <p class="text-gray-300 text-xs mt-0.5">Acuerdos de pago, llamadas, gestiones y notas</p>
            </div>
            <span class="text-gray-300 text-xs">{{ $seguimientos->count() }} registro(s)</span>
        </div>

        {{-- Formulario nuevo registro --}}
        @if(!$esReadOnly)
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <form method="POST" action="{{ route('cartera.seguimiento.store', $codigo) }}" class="flex flex-col gap-3">
                @csrf
                <div class="flex flex-wrap gap-3 items-start">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                        <select name="tipo"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="Llamada">Llamada</option>
                            <option value="Acuerdo">Acuerdo de pago</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Email">Email</option>
                            <option value="Visita">Visita</option>
                            <option value="Suspensión">Suspensión</option>
                            <option value="Nota" selected>Nota</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Descripción / Anotación</label>
                        <textarea name="nota" rows="2" required
                            placeholder="Ej: Se llamó al acudiente, acordó pagar el 15 de abril la mensualidad de marzo..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"></textarea>
                        @error('nota')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="pt-5">
                        <button type="submit"
                            class="bg-gray-700 hover:bg-gray-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                            Guardar registro
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif

        {{-- Lista de seguimientos --}}
        @if($seguimientos->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                Sin registros de seguimiento aún. Agrega el primero arriba.
            </div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($seguimientos as $s)
            @php
                $colores = [
                    'Llamada'  => 'bg-blue-100 text-blue-800',
                    'Acuerdo'  => 'bg-green-100 text-green-800',
                    'WhatsApp' => 'bg-emerald-100 text-emerald-800',
                    'Email'    => 'bg-purple-100 text-purple-800',
                    'Visita'   => 'bg-orange-100 text-orange-800',
                    'Suspensión' => 'bg-red-100 text-red-800',
                    'Nota'     => 'bg-gray-100 text-gray-700',
                ];
                $badge = $colores[$s->tipo] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <div class="px-5 py-4 hover:bg-gray-50">
                {{-- Vista normal --}}
                <div class="flex gap-4 items-start" id="view-{{ $s->id }}">
                    <div class="flex-shrink-0 pt-0.5">
                        <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full {{ $badge }}">{{ $s->tipo }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $s->nota }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($s->created_at)->format('d/m/Y H:i') }}
                            @if($s->usuario) · <span class="font-medium">{{ $s->usuario }}</span> @endif
                        </p>
                    </div>
                    @if(!$esReadOnly)
                    <div class="flex gap-2 flex-shrink-0 mt-0.5">
                        <button type="button" onclick="editarSeguimiento({{ $s->id }})"
                            class="text-yellow-600 hover:text-yellow-800 text-xs transition font-medium">Editar</button>
                        <form method="POST" action="{{ route('cartera.seguimiento.destroy', $s->id) }}"
                            onsubmit="return confirm('¿Eliminar este registro?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 text-xs transition">Eliminar</button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Formulario de edición (oculto por defecto) --}}
                @if(!$esReadOnly)
                <div id="edit-{{ $s->id }}" class="hidden mt-3">
                    <form method="POST" action="{{ route('cartera.seguimiento.update', $s->id) }}"
                        class="flex flex-wrap gap-3 items-start bg-gray-50 rounded-lg p-3 border border-gray-200">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                            <select name="tipo"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                @foreach(['Llamada','Acuerdo','WhatsApp','Email','Visita','Suspensión','Nota'] as $tipo)
                                    <option value="{{ $tipo }}" {{ $s->tipo === $tipo ? 'selected' : '' }}>
                                        {{ $tipo === 'Acuerdo' ? 'Acuerdo de pago' : $tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Descripción / Anotación</label>
                            <textarea name="nota" rows="2" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none">{{ $s->nota }}</textarea>
                        </div>
                        <div class="flex gap-2 pt-5">
                            <button type="submit"
                                class="bg-gray-700 hover:bg-gray-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                                Guardar
                            </button>
                            <button type="button" onclick="cancelarEdicion({{ $s->id }})"
                                class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-600 text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

@push('scripts')
<script>
    function editarSeguimiento(id) {
        document.getElementById('view-' + id).classList.add('hidden');
        document.getElementById('edit-' + id).classList.remove('hidden');
    }
    function cancelarEdicion(id) {
        document.getElementById('edit-' + id).classList.add('hidden');
        document.getElementById('view-' + id).classList.remove('hidden');
    }
</script>
@endpush

@endsection
