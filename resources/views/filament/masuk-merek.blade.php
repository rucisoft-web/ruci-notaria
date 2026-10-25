{{-- Panel merek di kiri halaman masuk. Di layar kecil hanya logo yang tampil di atas formulir. --}}
<aside class="nt-masuk-merek" aria-hidden="true">
    <div class="nt-masuk-logo">
        <img class="nt-logo-terang" src="{{ asset('img/logo/ruci-notaria.svg') }}" alt="Ruci Notaria">
        <img class="nt-logo-gelap" src="{{ asset('img/logo/ruci-notaria-putih.svg') }}" alt="Ruci Notaria">
    </div>
    <div class="nt-masuk-isi">
        <p class="nt-masuk-pita">Aplikasi kantor notaris &amp; PPAT</p>
        <h2>Akta tertib,<br>kantor siap diperiksa.</h2>
        <p class="nt-masuk-lead">Dari klien datang sampai minuta dijilid: penomoran terkunci, buku daftar dan laporan MPD tersusun sendiri, dan tenggat tidak lagi bergantung pada ingatan.</p>
        <ol>
            <li><span>01</span>Akta &amp; repertorium</li>
            <li><span>02</span>Buku daftar &amp; MPD</li>
            <li><span>03</span>Tenggat &amp; keuangan</li>
            <li><span>04</span>Protokol &amp; arsip</li>
        </ol>
    </div>
    <p class="nt-masuk-kaki">© {{ now()->year }} Rucisoft · Ruci Notaria</p>
    <div class="nt-masuk-garis"></div>
</aside>
