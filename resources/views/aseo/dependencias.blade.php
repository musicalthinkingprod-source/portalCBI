@extends('layouts.app-sidebar')

@section('header', 'Aseo · Dependencias')

@section('slot')

@if(session('ok'))
<div class="mb-5 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
@endif

<div class="grid lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow p-5 h-fit">
        <h3 class="font-semibold text-gray-700 mb-4">Nueva dependencia</h3>
        <form method="POST" action="{{ route('aseo.dependencias.store') }}" class="space-y-3">
            @csrf
            <div><label class="block text-xs text-gray-500 mb-1">Nombre</label><input type="text" name="nombre" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cocina Sede C, Servicios Generales…" value="{{ old('nombre') }}"></div>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 text-sm font-semibold">Guardar</button>
        </form>
    </div>
    <div class="lg:col-span-2 bg-white rounded-xl shadow">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-700">Dependencias ({{ $dependencias->count() }})</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs"><tr><th class="px-4 py-2 text-left">Nombre</th><th class="px-4 py-2 text-center">Activa</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dependencias as $d)
                    <tr><td class="px-4 py-2 text-gray-800">{{ $d->nombre }}</td><td class="px-4 py-2 text-center">{{ $d->activo ? '✓' : '—' }}</td></tr>
                    @empty
                    <tr><td colspan="2" class="px-4 py-6 text-center text-gray-400">Sin dependencias todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
