@php $hilo = $hilo ?? collect(); @endphp
<div class="mt-2 bg-gray-50 border border-gray-100 rounded-lg p-2.5">
    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5">💬 Hilo</p>
    @forelse($hilo as $c)
    <div class="flex items-start gap-2 mb-1.5">
        <div class="flex-1">
            <p class="text-xs leading-tight">
                <span class="font-semibold {{ $c->autor_rol === 'acudiente' ? 'text-emerald-700' : 'text-blue-700' }}">{{ $c->autor_nombre }}</span>
                <span class="text-gray-400">· {{ \Carbon\Carbon::parse($c->created_at)->locale('es')->isoFormat('D MMM, h:mm a') }}</span>
            </p>
            <p class="text-xs text-gray-700 whitespace-pre-line">{{ $c->mensaje }}</p>
        </div>
        @if($c->autor_rol === 'staff' && $c->autor_id === $miUser)
        <form method="POST" action="{{ route('bitacora.comentarios.destroy', $c->id) }}" onsubmit="return confirm('¿Eliminar tu comentario?');">
            @csrf @method('DELETE')
            <button type="submit" class="text-gray-300 hover:text-red-500 text-xs" title="Eliminar">✕</button>
        </form>
        @endif
    </div>
    @empty
    <p class="text-xs text-gray-400 mb-1.5">Sin respuestas aún. Escribe la primera.</p>
    @endforelse

    <form method="POST" action="{{ route('bitacora.comentar', $entradaId) }}" class="flex gap-2 mt-1">
        @csrf
        <input type="text" name="mensaje" required maxlength="4000" placeholder="Responder en el hilo…"
            class="flex-1 border border-gray-300 rounded-lg px-2 py-1 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-3 py-1 rounded-lg text-xs font-semibold whitespace-nowrap">Enviar</button>
    </form>
</div>
