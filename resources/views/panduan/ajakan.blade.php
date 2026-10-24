@php($tamu = \App\Http\Controllers\TamuController::tersedia())
<section class="rp-ajakan">
    <div>
        <h2>{{ $judulAjakan ?? 'Lihat sendiri dengan data contoh' }}</h2>
        <p>{{ $teksAjakan ?? 'Situs demo berisi tiga kantor notaris fiktif di Bandung, Cimahi, dan Bandung Barat lengkap dengan akta, buku daftar, tenggat, tagihan, dan protokol. Akun tamu hanya dapat melihat, tidak dapat mengubah data.' }}</p>
    </div>
    <div class="rp-ajakan-aksi">
        @if ($publik && $tamu)
            <form method="POST" action="{{ route('tamu.masuk') }}">
                @csrf
                <button type="submit" class="rp-tombol">Masuk sebagai tamu</button>
            </form>
        @endif
        @if ($publik)
            <a class="rp-tombol rp-tombol-garis" href="{{ url('/app/login') }}">Masuk dengan akun</a>
        @endif
        @if (($halaman ?? '') !== 'penggunaan')
            <a class="rp-tombol rp-tombol-garis" href="{{ $publik ? route('panduan.penggunaan') : \App\Filament\Pages\Panduan\PanduanPenggunaan::getUrl() }}">Baca panduan penggunaan</a>
        @else
            <a class="rp-tombol rp-tombol-garis" href="{{ $publik ? route('panduan.mengapa') : \App\Filament\Pages\Panduan\MengapaRuciNotaria::getUrl() }}">Mengapa Ruci Notaria</a>
        @endif
    </div>
</section>
