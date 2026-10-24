@extends('cetak.layout')
@section('judul', 'Label jilid minuta')
@push('gaya')
    .label { border: 2px solid #000; width: 120mm; margin: 30px auto; padding: 18px; text-align: center; line-height: 1.6; }
    .label h3 { margin: 0 0 6px; font-size: 16pt; }
@endpush
@section('isi')
    <div class="lembar">
        @foreach ([1, 2] as $salinan)
            <div class="label">
                <h3>MINUTA AKTA</h3>
                <div>{{ $j->jabatan->nama_lengkap }}<br>Notaris di {{ $j->jabatan->tempat_kedudukan }}</div>
                <div style="font-size: 14pt; margin-top: 8px"><b>{{ $namaBulan }} {{ $j->tahun }} · Jilid {{ $j->nomor_jilid }}</b></div>
                <div>Akta nomor urut {{ $j->nomor_awal }} sampai dengan {{ $j->nomor_akhir }}</div>
                <div>Jumlah {{ $j->jumlah_minuta }} minuta</div>
            </div>
        @endforeach
        <p class="catatan tengah">Label punggung dan sampul. Satu jilid berisi paling banyak 50 akta (Pasal 16 ayat 1 huruf g UUJN).</p>
    </div>
@endsection
