@extends('layouts.app-sidebar')

@section('header', 'Clave de respuestas · '.$grado['nombre'])

@section('slot')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
@if(session('error'))
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">⚠️ {{ session('error') }}</div>
@endif

<div class="mb-4 flex items-center justify-between">
    <a href="{{ route('admision.claves') }}" class="text-sm text-blue-700 hover:underline">← Volver</a>
    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ $grado['total'] }} preguntas</span>
</div>

<form method="POST" action="{{ route('admision.claves.update', $key) }}" id="clave-form"
      onsubmit="return confirm('¿Guardar la clave? Se recalificarán las evaluaciones ya registradas de {{ $grado['nombre'] }}.');">
    @csrf @method('PUT')

    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-800">Marca la respuesta correcta de cada pregunta</h2>
            <span id="progreso" class="text-xs text-gray-400">0 / {{ $grado['total'] }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-6">
            @foreach($grado['materias'] as $mat)
            <div>
                <h3 class="text-sm font-bold text-gray-700 border-b border-gray-200 pb-1 mb-2">{{ $mat['nombre'] }}</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] text-gray-400">
                            <th class="w-8 text-left font-medium">Nº</th>
                            @foreach(['A','B','C','D'] as $op)<th class="text-center font-medium">{{ $op }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mat['preguntas'] as $q)
                        @php $actual = old('clave.'.$q['n'], $clave[$q['n']] ?? null); @endphp
                        <tr class="border-t border-gray-50" title="{{ $q['enunciado'] }}">
                            <td class="py-1 text-gray-500 font-medium">{{ $q['n'] }}</td>
                            @foreach(['A','B','C','D'] as $op)
                            <td class="text-center py-1">
                                <input type="radio" name="clave[{{ $q['n'] }}]" value="{{ $op }}"
                                       class="clave-radio h-4 w-4 accent-green-700 cursor-pointer"
                                       @checked($actual===$op)>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admision.claves') }}" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancelar</a>
        <button type="submit" class="bg-green-700 hover:bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-semibold transition">
            Guardar clave
        </button>
    </div>
</form>

<script>
(function(){
    const total = {{ $grado['total'] }};
    const prog = document.getElementById('progreso');
    function actualizar(){
        const s = new Set();
        document.querySelectorAll('.clave-radio:checked').forEach(r => {
            const m = r.name.match(/clave\[(\d+)\]/); if (m) s.add(m[1]);
        });
        prog.textContent = s.size + ' / ' + total;
        prog.className = 'text-xs ' + (s.size===total ? 'text-green-600 font-semibold' : 'text-gray-400');
    }
    document.getElementById('clave-form').addEventListener('change', e => {
        if (e.target.classList.contains('clave-radio')) actualizar();
    });
    actualizar();
})();
</script>

@endsection
