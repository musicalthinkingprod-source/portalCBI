{{-- Menú desplegable de impresión con selección de período.
     Variables: $rutaBase (URL base de impresión), $titulo (texto del botón).
     Usa <details> nativo (sin JS). El período viaja por ?periodo=N; sin él imprime todos. --}}
@php
    $sep = str_contains($rutaBase, '?') ? '&' : '?';
@endphp
<details class="relative inline-block group" style="list-style:none;">
    <summary class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition cursor-pointer select-none list-none">
        🖨️ {{ $titulo ?? 'Imprimir' }} ▾
    </summary>
    <div class="absolute right-0 mt-1 w-52 bg-white rounded-lg shadow-xl border border-gray-200 z-50 py-1 text-gray-800">
        <a href="{{ $rutaBase }}" target="_blank"
           class="block px-4 py-2 text-xs hover:bg-blue-50 font-semibold text-blue-700">📄 Todos los períodos</a>
        <div class="border-t border-gray-100 my-1"></div>
        @foreach([1=>'1er período', 2=>'2º período', 3=>'3er período', 4=>'4º período'] as $n => $lbl)
            <a href="{{ $rutaBase . $sep . 'periodo=' . $n }}" target="_blank"
               class="block px-4 py-2 text-xs hover:bg-gray-100">{{ $lbl }}</a>
        @endforeach
    </div>
</details>
