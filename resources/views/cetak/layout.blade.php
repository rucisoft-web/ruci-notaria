<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') · {{ \App\Models\Pengaturan::get('kantor.nama') }}</title>
    <style>
        @page { size: A4 @yield('orientasi', 'portrait'); margin: 18mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11.5pt; color: #000; margin: 0; background: #e9eceb; }
        .lembar { background: #fff; width: 210mm; min-height: 297mm; margin: 12px auto; padding: 18mm 16mm; box-shadow: 0 2px 10px #0002; }
        .lembar.lanskap { width: 297mm; min-height: 210mm; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { font-size: 15pt; margin: 0; letter-spacing: .03em; }
        .kop p { margin: 2px 0 0; font-size: 10pt; }
        h2 { font-size: 13pt; text-align: center; margin: 6px 0 2px; text-transform: uppercase; }
        .sub { text-align: center; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        .nt-table th, .nt-table td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; font-size: 10.5pt; }
        .nt-table th { background: #f0f0f0; text-align: center; }
        .nt-table td.num { text-align: center; white-space: nowrap; }
        .nt-table tr.huruf td { background: #f6f6f6; }
        .nihil { text-align: center; font-size: 18pt; font-weight: bold; letter-spacing: .3em; margin: 40px 0; }
        .ttd { margin-top: 28px; width: 62%; margin-left: 38%; text-align: center; }
        .ttd .ruang { height: 70px; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .info td { padding: 2px 4px; vertical-align: top; }
        .info td:first-child { width: 32%; }
        .catatan { font-size: 9.5pt; color: #444; margin-top: 18px; }
        .ganti-halaman { page-break-before: always; }
        .alat { position: sticky; top: 0; background: #0b3a5c; color: #fff; padding: 8px 16px; font-family: system-ui, sans-serif; font-size: 14px; display: flex; gap: 12px; align-items: center; }
        .contoh { position: fixed; top: 45%; left: 0; right: 0; text-align: center; transform: rotate(-24deg); font: bold 72pt Arial, sans-serif; color: rgba(184, 137, 58, .16); letter-spacing: .12em; pointer-events: none; z-index: 5; }
        .alat button { background: #fff; color: #0b3a5c; border: 0; border-radius: 6px; padding: 6px 14px; font-weight: 600; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .alat { display: none; }
            .lembar { margin: 0; padding: 0; box-shadow: none; width: auto; min-height: 0; }
        }
        @stack('gaya')
    </style>
</head>
<body>
    <div class="alat"><button type="button" onclick="window.print()">Cetak / simpan PDF</button><span>@yield('judul')</span></div>
    @if (config('notaria.demo.enabled'))<div class="contoh">DATA CONTOH</div>@endif
    @yield('isi')
</body>
</html>
