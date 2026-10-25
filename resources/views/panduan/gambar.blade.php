{{-- Tangkapan layar dengan tempat (placeholder) berukuran tetap 16:10, sehingga halaman tidak bergeser saat gambar dimuat dan tidak pernah melebar ke samping. --}}
<figure class="rp-gambar">
    <div class="rp-gambar-bingkai" data-label="{{ $alt }}">
        <img src="{{ asset('img/panduan/'.$src.'.jpg') }}" alt="{{ $alt }}" width="1280" height="800" loading="lazy" decoding="async" onerror="this.remove()">
        @if (config('notaria.demo.enabled'))<span class="rp-label-contoh">DATA CONTOH</span>@endif
    </div>
    @isset($ket)<figcaption>{{ $ket }}</figcaption>@endisset
</figure>
