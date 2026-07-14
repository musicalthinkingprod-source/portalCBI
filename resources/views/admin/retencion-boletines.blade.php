@extends('layouts.app-sidebar')

@section('header', 'Retención de Boletines — Portal Padres')

@section('slot')

@if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-xl text-sm">✅ {{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">
        ⚠️ {{ $errors->first() }}
    </div>
@endif

{{-- Explicación --}}
<div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
    <p class="font-semibold mb-1">¿Para qué sirve esto?</p>
    <p>Al aplicar una retención, el portal de padres bloquea los módulos de <strong>Consultar promedios</strong>, <strong>Boletines</strong>, <strong>Recuperaciones</strong> y <strong>Salvavidas</strong> del estudiante. Al acudiente se le indica <strong>qué área retiene</strong> y <strong>con quién comunicarse</strong> para que lo activen. Solo el área que aplicó la retención (o SuperAd) puede levantarla.</p>
</div>

@if(empty($gestionables))
    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-800">
        Tu perfil no tiene tipos de retención habilitados.
    </div>
@else
{{-- Formulario aplicar retención --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">
    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Nueva retención</h3>
    </div>
    <div class="p-5">
        <form method="POST" action="{{ route('admin.retencion-boletines.store') }}"
              class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Código del estudiante <span class="text-red-500">*</span></label>
                <input type="number" name="codigo_alumno" value="{{ old('codigo_alumno') }}" required
                       placeholder="ej: 12345"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                              @error('codigo_alumno') border-red-400 @enderror">
                @error('codigo_alumno')
                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Área que retiene <span class="text-red-500">*</span></label>
                <select name="tipo" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               @error('tipo') border-red-400 @enderror">
                    @foreach($gestionables as $t)
                        <option value="{{ $t }}" {{ old('tipo') === $t ? 'selected' : '' }}>{{ $tipos[$t]['label'] }}</option>
                    @endforeach
                </select>
                @error('tipo')
                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Motivo <span class="text-gray-400">(opcional)</span></label>
                <input type="text" name="motivo" value="{{ old('motivo') }}" maxlength="200"
                       placeholder="ej: Pendiente compromiso académico firmado"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-4 flex justify-end">
                <button type="submit"
                        class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    Aplicar retención
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Listado --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between flex-wrap gap-2">
        <h3 class="font-bold text-sm uppercase tracking-wide">Retenciones activas</h3>
        <form method="GET" action="{{ route('admin.retencion-boletines.index') }}" class="flex gap-2">
            <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar por código o nombre..."
                   class="border border-blue-600 bg-blue-900 text-white placeholder-blue-300 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-white w-52">
            <button type="submit" class="bg-white text-blue-800 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-blue-50 transition">Buscar</button>
            @if($busqueda)
                <a href="{{ route('admin.retencion-boletines.index') }}" class="text-blue-300 text-xs px-2 py-1.5 hover:text-white transition">✕ Limpiar</a>
            @endif
        </form>
    </div>

    @if($retenciones->isEmpty())
        <div class="px-5 py-10 text-center text-gray-400 text-sm">
            No hay retenciones activas{{ $busqueda ? ' para esa búsqueda' : '' }}.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Código</th>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-left">Curso</th>
                        <th class="px-4 py-3 text-left">Área que retiene</th>
                        <th class="px-4 py-3 text-left">Motivo</th>
                        <th class="px-4 py-3 text-left">Retenido por</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($retenciones as $r)
                    @php $conf = $tipos[$r->tipo] ?? null; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-700">{{ $r->codigo_alumno }}</td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">{{ $r->NOMBRE1 }} {{ $r->NOMBRE2 }} {{ $r->APELLIDO1 }} {{ $r->APELLIDO2 }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $r->CURSO ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                🔒 {{ $conf['label'] ?? $r->tipo }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs">{{ $r->motivo ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $r->retenido_por }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(in_array($r->tipo, $gestionables, true))
                                <form method="POST" action="{{ route('admin.retencion-boletines.destroy', $r->id) }}"
                                      onsubmit="return confirm('¿Levantar esta retención? El acudiente podrá consultar boletines y promedios.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs text-red-500 hover:text-red-700 font-semibold transition">
                                        Levantar
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-300" title="Solo {{ $conf['contacto'] ?? 'el área correspondiente' }} puede levantarla">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
