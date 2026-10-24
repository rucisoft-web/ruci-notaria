@extends('cetak.layout')
@section('judul', $pengantar ? 'Laporan bulanan MPD '.$namaBulan.' '.$tahun : $buku[0]['judul'].' '.$namaBulan.' '.$tahun)
@section('orientasi', 'landscape')
@section('isi')
    @if ($pengantar)
        <div class="lembar">
            @include('cetak.partials.kop', ['jabatan' => $jabatan])
            <table class="info">
                <tr><td>Nomor</td><td>: ....../MPD/{{ str_pad($bulan, 2, '0', STR_PAD_LEFT) }}/{{ $tahun }}</td></tr>
                <tr><td>Lampiran</td><td>: {{ count($buku) }} (berkas daftar)</td></tr>
                <tr><td>Perihal</td><td>: Penyampaian salinan daftar akta dan daftar lain bulan {{ $namaBulan }} {{ $tahun }}</td></tr>
            </table>
            <p style="margin-top: 18px">Kepada Yth.<br>Ketua Majelis Pengawas Daerah Notaris<br>{{ $jabatan->tempat_kedudukan }}<br>di tempat</p>
            <p>Dengan hormat,</p>
            <p style="text-align: justify">Memenuhi ketentuan Pasal 61 Undang-Undang Nomor 2 Tahun 2014 tentang Perubahan atas Undang-Undang Nomor 30 Tahun 2004 tentang Jabatan Notaris, bersama ini saya sampaikan salinan yang telah disahkan dari daftar akta dan daftar lain yang saya buat pada bulan {{ $namaBulan }} {{ $tahun }}, yaitu:</p>
            <table class="nt-table" style="width: 80%; margin: 0 auto">
                <thead><tr><th>No.</th><th>Daftar</th><th>Jumlah</th></tr></thead>
                <tbody>
                    @foreach ($buku as $i => $b)
                        <tr><td class="num">{{ $i + 1 }}</td><td>{{ $b['judul'] }}</td>
                            <td class="num">{{ $b['isi']->isEmpty() ? 'Nihil' : ($b['jenis'] === 'klapper' ? $b['isi']->flatten(1)->count().' nama' : $b['isi']->count()) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <p>Demikian disampaikan, atas perhatiannya saya ucapkan terima kasih.</p>
            <div class="ttd">
                {{ $jabatan->tempat_kedudukan }}, {{ today()->translatedFormat('d F Y') }}<br>Notaris {{ $jabatan->tempat_kedudukan }},
                <div class="ruang"></div>
                <b><u>{{ $jabatan->nama_lengkap }}</u></b>
            </div>
        </div>
    @endif

    @foreach ($buku as $b)
        <div class="lembar lanskap {{ $pengantar || ! $loop->first ? 'ganti-halaman' : '' }}">
            <h2>{{ $b['judul'] }}</h2>
            <p class="sub">{{ $jabatan->nama_lengkap }}, Notaris di {{ $jabatan->tempat_kedudukan }}<br>Bulan {{ $namaBulan }} {{ $tahun }}</p>
            @include('cetak.partials.buku', ['jenis' => $b['jenis'], 'isi' => $b['isi']])
            <div class="ttd">
                Salinan ini sesuai dengan aslinya.<br>{{ $jabatan->tempat_kedudukan }}, {{ today()->translatedFormat('d F Y') }}<br>Notaris {{ $jabatan->tempat_kedudukan }},
                <div class="ruang"></div>
                <b><u>{{ $jabatan->nama_lengkap }}</u></b>
            </div>
        </div>
    @endforeach
@endsection
