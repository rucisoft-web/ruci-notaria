@extends('cetak.layout')
@section('judul', 'Tanda terima '.$tt->nomor)
@section('isi')
    <div class="lembar">
        @include('cetak.partials.kop', ['jabatan' => $tt->berkas->jabatan])
        <h2>Tanda Terima Dokumen</h2>
        <p class="sub">Nomor {{ $tt->nomor }}</p>
        <table class="info">
            <tr><td>Tanggal</td><td>: {{ $tt->tanggal->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Berkas</td><td>: {{ $tt->berkas->nomor }} · {{ $tt->berkas->judul ?? '' }}</td></tr>
            <tr><td>Klien</td><td>: {{ $tt->berkas->klien?->nama }}</td></tr>
            <tr><td>{{ $tt->arah === 'masuk' ? 'Diserahkan oleh' : 'Diterima oleh' }}</td><td>: {{ $tt->pihak }}</td></tr>
        </table>
        <p style="margin-top: 14px">{{ $tt->arah === 'masuk' ? 'Telah diterima oleh kantor notaris dokumen berikut:' : 'Telah diserahkan oleh kantor notaris dokumen berikut:' }}</p>
        <table class="nt-table">
            <thead><tr><th style="width: 40px">No.</th><th>Dokumen</th></tr></thead>
            <tbody>
                @foreach (preg_split('/\r\n|\r|\n/', trim($tt->daftar_dokumen)) as $i => $baris)
                    @continue(trim($baris) === '')
                    <tr><td class="num">{{ $loop->iteration }}</td><td>{{ $baris }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <table style="margin-top: 30px; text-align: center">
            <tr><td>Yang menyerahkan,</td><td>Yang menerima,</td></tr>
            <tr><td style="height: 70px"></td><td></td></tr>
            <tr>
                <td>( {{ $tt->arah === 'masuk' ? $tt->pihak : $tt->createdBy?->name }} )</td>
                <td>( {{ $tt->arah === 'masuk' ? $tt->createdBy?->name : $tt->pihak }} )</td>
            </tr>
        </table>
    </div>
@endsection
