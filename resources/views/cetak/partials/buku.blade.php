{{-- Tabel satu buku daftar; dipakai layar Buku Daftar dan cetakan. Buku kosong ditulis NIHIL. --}}
@php($kosong = $isi->isEmpty())
@if ($kosong)
    <p class="nihil">NIHIL</p>
@elseif (in_array($jenis, ['repertorium', 'wasiat', 'protes'], true))
    <table class="nt-table">
        <thead>
            <tr>
                <th>No. urut</th><th>No. bulanan</th><th>Tanggal</th><th>Sifat akta</th><th>Nama penghadap dan/atau yang diwakili/kuasa</th><th>Hal.</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($isi as $r)
                <tr>
                    <td class="num">{{ $r['nomor_urut'] }}</td>
                    <td class="num">{{ $r['nomor_bulanan'] }}</td>
                    <td>{{ $r['tanggal']->format('d-m-Y') }}<br><small>{{ $r['jam'] }}</small></td>
                    <td>{{ $r['sifat'] }}</td>
                    <td>@foreach ($r['penghadap'] as $i => $p){{ $i + 1 }}. {{ $p }}<br>@endforeach</td>
                    <td class="num">{{ $r['halaman'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@elseif ($jenis === 'klapper')
    <table class="nt-table">
        <thead><tr><th>Nama penghadap</th><th>Sifat akta</th><th>Nomor</th><th>Tanggal</th></tr></thead>
        <tbody>
            @foreach ($isi as $huruf => $baris)
                <tr class="huruf"><td colspan="4"><b>{{ $huruf }}</b></td></tr>
                @foreach ($baris as $r)
                    <tr>
                        <td>{{ $r['nama'] }}</td>
                        <td>{{ $r['sifat'] }}</td>
                        <td class="num">{{ $r['nomor_bulanan'] }}</td>
                        <td>{{ $r['tanggal']->format('d-m-Y') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
@else
    <table class="nt-table">
        <thead>
            <tr>
                <th>No. urut</th><th>Nomor</th><th>Tanggal</th>
                @if ($jenis === 'waarmerking')<th>Tanggal surat</th>@endif
                <th>Sifat/judul surat</th><th>Nama para pihak</th><th>Lembar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($isi as $s)
                <tr>
                    <td class="num">{{ $s->nomor_urut }}</td>
                    <td>{{ $s->nomor }}</td>
                    <td>{{ $s->tanggal->format('d-m-Y') }}</td>
                    @if ($jenis === 'waarmerking')<td>{{ $s->tanggal_surat?->format('d-m-Y') }}</td>@endif
                    <td>{{ $s->judul }}@if ($s->batal)<br><b>BATAL</b>: {{ $s->alasan_batal }}@endif</td>
                    <td>{!! nl2br(e($s->para_pihak)) !!}</td>
                    <td class="num">{{ $s->jumlah_lembar }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
