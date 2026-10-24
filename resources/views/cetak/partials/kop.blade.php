@php($kantor = \App\Http\Controllers\CetakController::kantor())
<div class="kop">
    <h1>{{ $jabatan->nama_lengkap }}</h1>
    <p>Notaris{{ $jabatan->is_ppat ? ' & Pejabat Pembuat Akta Tanah' : '' }} · {{ $jabatan->tempat_kedudukan }}</p>
    @if ($jabatan->nomor_sk)<p>SK {{ $jabatan->nomor_sk }}{{ $jabatan->tanggal_sk ? ' tanggal '.$jabatan->tanggal_sk->translatedFormat('d F Y') : '' }}</p>@endif
    <p>{{ $jabatan->alamat_kantor }}{{ $kantor['telepon'] ? ' · Telp. '.$kantor['telepon'] : '' }}</p>
</div>
