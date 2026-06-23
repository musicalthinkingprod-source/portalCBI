@extends('layouts.app-sidebar')

@section('header', 'Aseo · Entregas a dependencias')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif

<div class="flex justify-between items-center mb-5">
    <h3 class="font-semibold text-gray-700">Últimas entregas</h3>
    <a href="{{ route('aseo.salidas.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📤 Nueva entrega</a>
</div>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
            <tr><th class="px-4 py-2 text-left">Fecha</th><th class="px-4 py-2 text-left">Dependencia</th><th class="px-4 py-2 text-left">Entregó</th><th class="px-4 py-2 text-left">Observación</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($salidas as $s)
            <tr>
                <td class="px-4 py-2 text-gray-600">{{ \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') }}</td>
                <td class="px-4 py-2 text-gray-800">{{ $s->dependencia }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $s->entregado_por }}</td>
                <td class="px-4 py-2 text-gray-500">{{ $s->observacion }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Sin entregas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
