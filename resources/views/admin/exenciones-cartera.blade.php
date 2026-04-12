@extends('layouts.app-sidebar')

@section('header', 'Exenciones de Cartera — Portal Padres')

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
    <p>Cuando un padre tiene saldo pendiente mayor a $100.000, el portal le bloquea los módulos de <strong>Consultar promedios</strong> y <strong>Boletines</strong>. Aquí puedes crear una exención para un estudiante específico, de modo que sus padres puedan acceder aunque haya deuda — útil para acuerdos de pago u otras situaciones.</p>
</div>

{{-- Formulario agregar exención --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">
    <div class="px-5 py-3 bg-blue-800 text-white">
        <h3 class="font-bold text-sm uppercase tracking-wide">Nueva exención</h3>
    </div>
    <div class="p-5">
        <form method="POST" action="{{ route('admin.exenciones-cartera.store') }}"
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
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Motivo</label>
                <input type="text" name="motivo" value="{{ old('motivo') }}" maxlength="200"
                       placeholder="ej: Acuerdo de pago aprobado por dirección"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Vence el <span class="text-gray-400">(opcional)</span></label>
                <input type="date" name="vence_en" value="{{ old('vence_en') }}"
                       min="{{ today()->addDay()->toDateString() }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                              @error('vence_en') border-red-400 @enderror">
                @error('vence_en')
                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                @enderror
                <p class="text-gray-400 text-xs mt-0.5">Sin fecha = no vence</p>
            </div>
            {{-- Nota en seguimiento (opcional) --}}
            <div class="sm:col-span-4 border-t border-gray-100 pt-4" x-data="{ abierto: {{ old('nota_seguimiento') ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2 cursor-pointer select-none w-fit">
                    <input type="checkbox" x-model="abierto" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-600">Registrar nota en seguimiento de cartera</span>
                </label>
                <div x-show="abierto" x-transition class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                        <select name="tipo_seguimiento"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Nota"    {{ old('tipo_seguimiento','Nota')    === 'Nota'    ? 'selected' : '' }}>Nota</option>
                            <option value="Acuerdo" {{ old('tipo_seguimiento') === 'Acuerdo' ? 'selected' : '' }}>Acuerdo</option>
                            <option value="Llamada" {{ old('tipo_seguimiento') === 'Llamada' ? 'selected' : '' }}>Llamada</option>
                            <option value="Email"   {{ old('tipo_seguimiento') === 'Email'   ? 'selected' : '' }}>Email</option>
                            <option value="Otro"    {{ old('tipo_seguimiento') === 'Otro'    ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs text-gray-500 mb-1">Nota</label>
                        <textarea name="nota_seguimiento" rows="2" maxlength="2000"
                                  placeholder="ej: Padre se comprometió a pagar el 50% el viernes 17..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('nota_seguimiento') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="sm:col-span-4 flex justify-end">
                <button type="submit"
                        class="bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    Crear exención
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Listado --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-3 bg-blue-800 text-white flex items-center justify-between flex-wrap gap-2">
        <h3 class="font-bold text-sm uppercase tracking-wide">Exenciones registradas</h3>
        <form method="GET" action="{{ route('admin.exenciones-cartera.index') }}" class="flex gap-2">
            <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar por código o nombre..."
                   class="border border-blue-600 bg-blue-900 text-white placeholder-blue-300 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-white w-52">
            <button type="submit" class="bg-white text-blue-800 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-blue-50 transition">Buscar</button>
            @if($busqueda)
                <a href="{{ route('admin.exenciones-cartera.index') }}" class="text-blue-300 text-xs px-2 py-1.5 hover:text-white transition">✕ Limpiar</a>
            @endif
        </form>
    </div>

    @if($exenciones->isEmpty())
        <div class="px-5 py-10 text-center text-gray-400 text-sm">
            No hay exenciones registradas{{ $busqueda ? ' para esa búsqueda' : '' }}.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-left">Curso</th>
                        <th class="px-4 py-3 text-left">Motivo</th>
                        <th class="px-4 py-3 text-center">Vence</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-left">Creado por</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($exenciones as $ex)
                    <tr class="hover:bg-gray-50 {{ $ex->vencida ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">{{ $ex->NOMBRE1 }} {{ $ex->NOMBRE2 }} {{ $ex->APELLIDO1 }} {{ $ex->APELLIDO2 }}</p>
                            <p class="text-xs text-gray-400">Código {{ $ex->codigo_alumno }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $ex->CURSO ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs">{{ $ex->motivo ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">
                            @if($ex->vence_en)
                                {{ \Carbon\Carbon::parse($ex->vence_en)->format('d/m/Y') }}
                            @else
                                <span class="text-gray-400">Sin vencimiento</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($ex->vencida)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                                    Vencida
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    ✓ Activa
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $ex->creado_por }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($ex->created_at)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.exenciones-cartera.destroy', $ex->id) }}"
                                  onsubmit="return confirm('¿Revocar esta exención?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-xs text-red-500 hover:text-red-700 font-semibold transition">
                                    Revocar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
