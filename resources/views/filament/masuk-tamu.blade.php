<div class="nt-tamu">
    <div class="nt-tamu-garis"><span>atau</span></div>
    <form method="POST" action="{{ route('tamu.masuk') }}">
        @csrf
        <button type="submit" class="nt-tamu-tombol">Masuk sebagai tamu</button>
    </form>
    <div class="nt-tamu-info">
        <p><b>Coba tanpa mendaftar.</b> Anda masuk sebagai notaris contoh di kantor fiktif dengan tiga notaris: Bandung, Cimahi, dan Bandung Barat.</p>
        <ul>
            <li>Lihat dasbor tenggat, register akta, buku daftar, dan paket laporan bulanan MPD.</li>
            <li>Coba alur akta: tambah penghadap dan saksi, buat draf .docx, periksa kesiapan, lalu beri nomor.</li>
            <li>Cetak repertorium, klapper, legalisasi, tagihan, dan kuitansi.</li>
        </ul>
        <p class="nt-tamu-kecil">Akun tamu dipakai bersama pengunjung lain. Pengaturan kantor, pengguna, profil, dan fitur AI tidak dapat diubah. Jangan memasukkan data pribadi sungguhan.@if (config('notaria.demo.reset_harian')) Data dikembalikan ke keadaan awal setiap hari.@endif</p>
    </div>
</div>
