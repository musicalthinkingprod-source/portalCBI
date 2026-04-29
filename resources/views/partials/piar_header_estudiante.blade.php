{{-- Header compacto del estudiante para pestañas del PIAR --}}
<div class="bg-white rounded-xl shadow px-4 py-3 mb-4 flex items-center gap-4 no-print">
    @include('partials.foto_estudiante', [
        'fotoDrive' => $estudiante->FOTO_DRIVE ?? null,
        'width'     => 64,
        'height'    => 80,
        'estilo'    => 'avatar',
    ])
    <div class="min-w-0">
        <h3 class="text-base sm:text-lg font-bold text-blue-900 truncate">
            {{ trim(($estudiante->APELLIDO1 ?? '') . ' ' . ($estudiante->APELLIDO2 ?? '')) }}
            {{ trim(($estudiante->NOMBRE1 ?? '') . ' ' . ($estudiante->NOMBRE2 ?? '')) }}
        </h3>
        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
            <span>Código <strong class="text-gray-800">{{ $estudiante->CODIGO ?? '—' }}</strong></span>
            @if(!empty($estudiante->CURSO))
                <span>Curso <strong class="text-gray-800">{{ $estudiante->CURSO }}</strong></span>
            @endif
            @if(($estudiante->GRADO ?? null) !== null && $estudiante->GRADO !== '')
                <span>Grado <strong class="text-gray-800">{{ $estudiante->GRADO }}</strong></span>
            @endif
            @if(!empty($estudiante->TAR_ID) || !empty($estudiante->REG_CIVIL))
                <span>Doc <strong class="text-gray-800">{{ $estudiante->TAR_ID ?: $estudiante->REG_CIVIL }}</strong></span>
            @endif
        </div>
    </div>
</div>
