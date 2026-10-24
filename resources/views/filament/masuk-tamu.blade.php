<div class="nt-tamu">
    @if ($tamu)
        <div class="nt-tamu-garis"><span>atau</span></div>
        <form method="POST" action="{{ route('tamu.masuk') }}">
            @csrf
            <button type="submit" class="nt-tamu-tombol">Masuk sebagai tamu (hanya lihat)</button>
        </form>
        @include('filament.tautan-panduan')
        <div class="nt-tamu-info">
            <p><b>Coba tanpa mendaftar.</b> Anda melihat kantor fiktif dengan tiga notaris di Bandung, Cimahi, dan Bandung Barat, lengkap dengan data contoh.</p>
            <ul>
                <li>Lihat dasbor tenggat, register akta, buku daftar, dan paket laporan bulanan MPD.</li>
                <li>Telusuri alur akta: penghadap dan saksi, draf, pemeriksaan kesiapan, nomor, salinan, dan grosse.</li>
                <li>Buka cetakan repertorium, klapper, legalisasi, tagihan, kuitansi, dan label jilid minuta.</li>
            </ul>
            <p class="nt-tamu-kecil">Akun tamu hanya dapat melihat. Tombol tambah, ubah, hapus, dan beri nomor disembunyikan, dan data tidak dapat diubah.@if (config('notaria.demo.reset_harian')) Data dikembalikan ke keadaan awal setiap hari.@endif</p>
        </div>
    @else
        @include('filament.tautan-panduan')
    @endif
</div>
