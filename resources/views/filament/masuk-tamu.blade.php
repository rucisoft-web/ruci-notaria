<div class="nt-masuk-tambahan">
    @if ($tamu)
        <div class="nt-masuk-pemisah"><span>atau coba tanpa akun</span></div>
        <form class="nt-tamu-form" method="POST" action="{{ route('tamu.masuk') }}">
            @csrf
            @if ($notaris->isNotEmpty())
            <fieldset>
                <legend>Pilih notaris contoh</legend>
                <div class="nt-tamu-pilihan">
                    @foreach ($notaris as $j)
                        <label>
                            <input type="radio" name="notaris" value="{{ $j->kode }}" @checked($loop->first)>
                            <span><b>{{ $j->nama }}</b><small>{{ str_replace('Kabupaten', 'Kab.', $j->tempat_kedudukan) }}</small></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @endif
            <button type="submit" class="nt-tamu-tombol">Masuk sebagai Tamu</button>
            <p class="nt-tamu-catatan">Mode hanya lihat dengan data contoh fiktif. Notaris dapat diganti kapan saja dari pita di atas layar.@if (config('notaria.demo.reset_harian')) Data dikembalikan ke keadaan awal setiap hari.@endif</p>
        </form>
    @endif
    <a class="nt-masuk-pelajari" href="{{ route('panduan.mengapa') }}">Mengapa notaris memerlukan Ruci Notaria <span aria-hidden="true">→</span></a>
    <a class="nt-masuk-panduan" href="{{ route('panduan.penggunaan') }}">Panduan penggunaan</a>
    <p class="nt-masuk-percaya">Terenkripsi · Akses berbasis peran · Jejak audit berantai</p>
</div>
