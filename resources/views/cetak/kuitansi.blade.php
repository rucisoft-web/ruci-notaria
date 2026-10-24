@extends('cetak.layout')
@section('judul', 'Kuitansi '.$p->nomor_kuitansi)
@section('isi')
    @php($t = $p->tagihan)
    <div class="lembar">
        @include('cetak.partials.kop', ['jabatan' => $t->berkas->jabatan])
        <h2>Kuitansi</h2>
        <p class="sub">Nomor {{ $p->nomor_kuitansi }}</p>
        <table class="info">
            <tr><td>Telah terima dari</td><td>: {{ $t->berkas->klien?->nama }}</td></tr>
            <tr><td>Uang sejumlah</td><td>: <b>{{ \App\Support\Money::format($p->jumlah) }}</b></td></tr>
            <tr><td>Terbilang</td><td>: <i>{{ \App\Support\Terbilang::rupiah($p->jumlah, false) }}</i></td></tr>
            <tr><td>Untuk pembayaran</td><td>: Tagihan {{ $t->nomor }} (berkas {{ $t->berkas->nomor }})</td></tr>
            <tr><td>Cara bayar</td><td>: {{ \App\Models\Pembayaran::METODE[$p->metode] ?? $p->metode }}</td></tr>
            @if ($p->catatan)<tr><td>Catatan</td><td>: {{ $p->catatan }}</td></tr>@endif
        </table>
        <div class="ttd">
            {{ $t->berkas->jabatan->tempat_kedudukan }}, {{ $p->tanggal->translatedFormat('d F Y') }}<br>Penerima,
            <div class="ruang"></div>
            <b><u>{{ \App\Models\Pengaturan::get('kantor.nama') }}</u></b>
        </div>
    </div>
@endsection
