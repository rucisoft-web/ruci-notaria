<div class="nt-pita-tamu" role="note">
    <span class="label">Tamu · hanya lihat</span>
    <span>Notaris contoh yang ditampilkan lebih dulu di buku daftar dan formulir:</span>
    <nav aria-label="Ganti notaris contoh">
        @foreach ($notaris as $j)
            <a href="{{ route('tamu.notaris', $j->kode) }}" @class(['aktif' => $j->id === $pilihan])>{{ $j->nama }} <small>({{ str_replace('Kabupaten', 'Kab.', $j->tempat_kedudukan) }})</small></a>
        @endforeach
    </nav>
</div>
