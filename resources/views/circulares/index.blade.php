@extends('layouts.app-sidebar')

@section('header', 'Circulares')

@section('slot')

{{-- Barra de filtros + acción --}}
<div class="bg-white rounded-xl shadow p-5 mb-6 flex flex-wrap gap-3 items-end justify-between">
    <form method="GET" action="{{ route('circulares.index') }}" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Año</label>
            <select name="año" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @foreach($años as $a)
                    <option value="{{ $a }}" @selected($a == $año)>{{ $a }}</option>
                @endforeach
                <option value="{{ date('Y') }}" @selected(!$años->contains($año))>{{ date('Y') }}</option>
            </select>
        </div>
    </form>
    <a href="{{ route('circulares.create') }}"
        class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
        + Nueva circular
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-300 text-green-800 rounded-lg px-4 py-3 mb-4 text-sm">
        {{ session('success') }}
    </div>
@endif

{{-- Tabla --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    @if($circulares->isEmpty())
        <p class="text-center text-gray-400 py-10 text-sm">No hay circulares para el año {{ $año }}.</p>
    @else
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Número</th>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Asunto</th>
                    <th class="px-4 py-3 text-left">Dirigido a</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($circulares as $c)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono font-semibold text-blue-800">{{ $c->numero }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->fecha->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $c->asunto }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->dirigido_a }}</td>
                    <td class="px-4 py-3">
                        @if($c->estado === 'publicada')
                            <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">Publicada</span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 text-xs font-semibold px-2 py-1 rounded-full">Borrador</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('circulares.show', $c) }}"
                            class="text-blue-600 hover:underline text-xs">Ver</a>
                        <a href="{{ route('circulares.edit', $c) }}"
                            class="text-gray-600 hover:underline text-xs">Editar</a>
                        <a href="{{ route('circulares.pdf', $c) }}" target="_blank"
                            class="text-red-600 hover:underline text-xs">PDF</a>
                        <form method="POST" action="{{ route('circulares.destroy', $c) }}"
                            class="inline"
                            onsubmit="return confirm('¿Eliminar la circular {{ $c->numero }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:underline text-xs">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
