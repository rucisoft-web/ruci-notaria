@extends('cetak.layout')
@section('judul', 'Tagihan '.$t->nomor)
@section('isi')
    @php($kelompok = $t->items->groupBy('jenis'))
    <div class="lembar">
        @include('cetak.partials.kop', ['jabatan' => $t->berkas->jabatan])
        <h2>{{ $t->status->value === 'lunas' ? 'Tagihan (Lunas)' : 'Tagihan' }}</h2>
        <p class="sub">Nomor {{ $t->nomor }}</p>
        <table class="info">
            <tr><td>Kepada</td><td>: {{ $t->berkas->klien?->nama }}</td></tr>
            <tr><td>Berkas</td><td>: {{ $t->berkas->nomor }}</td></tr>
            <tr><td>Tanggal</td><td>: {{ $t->tanggal->translatedFormat('d F Y') }}</td></tr>
            @if ($t->jatuh_tempo)<tr><td>Jatuh tempo</td><td>: {{ $t->jatuh_tempo->translatedFormat('d F Y') }}</td></tr>@endif
        </table>
        <table class="nt-table" style="margin-top: 14px">
            <thead><tr><th style="width: 40px">No.</th><th>Uraian</th><th style="width: 45mm">Jumlah</th></tr></thead>
            <tbody>
                @php($n = 0)
                @foreach (\App\Models\TagihanItem::JENIS as $jenis => $label)
                    @continue(! isset($kelompok[$jenis]))
                    <tr class="huruf"><td colspan="3"><b>{{ $label }}</b></td></tr>
                    @foreach ($kelompok[$jenis] as $item)
                        <tr><td class="num">{{ ++$n }}</td><td>{{ $item->uraian }}</td><td class="kanan">{{ \App\Support\Money::format($item->jumlah) }}</td></tr>
                    @endforeach
                @endforeach
                <tr><td colspan="2" class="kanan"><b>Total</b></td><td class="kanan"><b>{{ \App\Support\Money::format($t->total()) }}</b></td></tr>
                @if ($t->terbayar() > 0)
                    <tr><td colspan="2" class="kanan">Sudah dibayar</td><td class="kanan">{{ \App\Support\Money::format($t->terbayar()) }}</td></tr>
                    <tr><td colspan="2" class="kanan"><b>Sisa</b></td><td class="kanan"><b>{{ \App\Support\Money::format($t->sisa()) }}</b></td></tr>
                @endif
            </tbody>
        </table>
        <p><i>Terbilang: {{ \App\Support\Terbilang::rupiah($t->sisa() > 0 ? $t->sisa() : $t->total(), false) }}</i></p>
        @if ($t->catatan)<p>{{ $t->catatan }}</p>@endif
        <p class="catatan">Titipan pihak ketiga (PNBP, pajak, biaya BPN) diteruskan kepada instansi terkait dan bukan honorarium notaris.</p>
        <div class="ttd">
            {{ $t->berkas->jabatan->tempat_kedudukan }}, {{ $t->tanggal->translatedFormat('d F Y') }}
            <div class="ruang"></div>
            <b><u>{{ \App\Models\Pengaturan::get('kantor.nama') }}</u></b>
        </div>
    </div>
@endsection
