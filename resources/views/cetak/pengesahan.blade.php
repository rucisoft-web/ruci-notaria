@extends('cetak.layout')
@section('judul', 'Pengesahan '.$s->nomor)
@push('gaya')
    .cap { border: 2px solid #000; padding: 14px 18px; width: 150mm; margin: 0 auto; text-align: justify; line-height: 1.5; }
@endpush
@section('isi')
    @php($j = $s->jabatan)
    <div class="lembar">
        <div class="cap">
            <p class="tengah"><b>{{ match ($s->jenis->value) { 'legalisasi' => 'LEGALISASI', 'waarmerking' => 'WAARMERKING', default => 'PENGESAHAN FOTOKOPI' } }}</b><br>Nomor: {{ $s->nomor }}</p>
            @if ($s->jenis->value === 'legalisasi')
                <p>Saya, <b>{{ $j->nama_lengkap }}</b>, Notaris di {{ $j->tempat_kedudukan }}, dengan ini menerangkan bahwa {{ str_replace(["\r\n", "\n"], ', ', trim($s->para_pihak)) }}, yang saya, Notaris, kenal/yang telah diperkenalkan kepada saya, Notaris, telah membubuhkan tanda tangannya di hadapan saya, Notaris, pada surat di bawah tangan ini, setelah isi surat ini saya bacakan dan jelaskan kepadanya.</p>
                <p>Surat ini didaftarkan dalam buku daftar khusus pada tanggal {{ \App\Support\Terbilang::tanggal($s->tanggal) }} ({{ $s->tanggal->format('d-m-Y') }}).</p>
            @elseif ($s->jenis->value === 'waarmerking')
                <p>Dibukukan dalam buku daftar yang disediakan untuk itu oleh saya, <b>{{ $j->nama_lengkap }}</b>, Notaris di {{ $j->tempat_kedudukan }}, pada tanggal {{ \App\Support\Terbilang::tanggal($s->tanggal) }} ({{ $s->tanggal->format('d-m-Y') }}). Surat tertanggal {{ $s->tanggal_surat?->format('d-m-Y') ?? '…' }}, terdiri dari {{ \App\Support\Terbilang::angka($s->jumlah_lembar) }} ({{ $s->jumlah_lembar }}) lembar.</p>
            @else
                <p>Fotokopi ini sesuai dengan aslinya yang diperlihatkan kepada saya, <b>{{ $j->nama_lengkap }}</b>, Notaris di {{ $j->tempat_kedudukan }}, terdiri dari {{ \App\Support\Terbilang::angka($s->jumlah_lembar) }} ({{ $s->jumlah_lembar }}) lembar.</p>
            @endif
            <div style="width: 70%; margin-left: 30%; text-align: center; margin-top: 10px">
                {{ $j->tempat_kedudukan }}, {{ $s->tanggal->translatedFormat('d F Y') }}<br>Notaris {{ $j->tempat_kedudukan }},
                <div style="height: 70px"></div>
                <b><u>{{ $j->nama_lengkap }}</u></b>
            </div>
        </div>
        <p class="catatan tengah">Contoh redaksi. Sesuaikan dengan redaksi baku kantor sebelum dipakai.</p>
    </div>
@endsection
