@extends('cetak.layout')
@section('judul', 'Lembar tanda tangan '.$akta->judul)
@push('gaya')
    .kotak td { border: 1px solid #000; padding: 6px; vertical-align: top; }
    .kotak .ttd-ruang { height: 80px; }
    .kotak .jari { width: 30mm; }
@endpush
@section('isi')
    <div class="lembar">
        <h2>Daftar Hadir dan Tanda Tangan</h2>
        <p class="sub">{{ $akta->judul }}<br>{{ $akta->isBernomor() ? 'Nomor '.$akta->nomor_bulanan.', tanggal '.$akta->tanggal->translatedFormat('d F Y') : 'Jadwal '.($akta->jadwal_at?->translatedFormat('d F Y H:i') ?? '-') }}</p>
        <p><b>Penghadap</b></p>
        <table class="kotak">
            @foreach ($akta->penghadap->sortBy('urutan') as $p)
                <tr>
                    <td style="width: 30px">{{ $loop->iteration }}.</td>
                    <td>{{ \App\Services\BukuDaftar::uraianPenghadap($p) }}<br><small>{{ $p->keterangan_kedudukan }}</small></td>
                    <td class="ttd-ruang" style="width: 55mm"></td>
                    <td class="jari"><small>Sidik jari{{ $p->sidik_jari ? '' : ' (bila diminta)' }}</small></td>
                </tr>
            @endforeach
        </table>
        <p style="margin-top: 16px"><b>Saksi</b></p>
        <table class="kotak">
            @foreach ($akta->saksi->where('jenis', 'akta') as $s)
                <tr>
                    <td style="width: 30px">{{ $loop->iteration }}.</td>
                    <td>{{ $s->data()['nama'] ?? $s->subjek->nama }}</td>
                    <td class="ttd-ruang" style="width: 55mm"></td>
                </tr>
            @endforeach
        </table>
        <div class="ttd">
            Notaris,
            <div class="ruang"></div>
            <b><u>{{ $akta->jabatan->nama_lengkap }}</u></b>
        </div>
        <p class="catatan">Lembar bantu kantor; bukan bagian dari minuta. Tanda tangan dan sidik jari pada minuta tetap dibubuhkan sesuai Pasal 16 ayat 1 huruf c dan Pasal 44 UUJN.</p>
    </div>
@endsection
