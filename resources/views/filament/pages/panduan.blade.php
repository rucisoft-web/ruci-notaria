<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/panduan.css') }}?v={{ @filemtime(public_path('css/panduan.css')) }}">
    <div class="rp">
        <p class="rp-catatan">Halaman ini juga dapat dibuka tanpa login dan dibagikan kepada rekan notaris: <a href="{{ $urlPublik }}" target="_blank" rel="noopener">{{ $urlPublik }}</a></p>
        @include($isi, ['publik' => false])
    </div>
</x-filament-panels::page>
