<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} · Ruci Notaria</title>
    <meta name="description" content="{{ $ringkas }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="Ruci Notaria">
    <meta property="og:title" content="{{ $judul }}">
    <meta property="og:description" content="{{ $ringkas }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/og-ruci-notaria.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="{{ asset('css/panduan.css') }}?v={{ @filemtime(public_path('css/panduan.css')) }}">
</head>
<body class="rp-halaman">
<header class="rp-kepala">
    <div class="rp-wadah rp-kepala-isi">
        <a href="{{ config('notaria.landing') ? url('/') : route('panduan.mengapa') }}" class="rp-merek"><img src="{{ asset('img/logo/ruci-notaria.svg') }}" alt="Ruci Notaria" width="188" height="40"></a>
        <nav class="rp-nav" aria-label="Panduan">
            <a href="{{ route('panduan.mengapa') }}" @class(['aktif' => request()->routeIs('panduan.mengapa')])>Mengapa Ruci Notaria</a>
            <a href="{{ route('panduan.penggunaan') }}" @class(['aktif' => request()->routeIs('panduan.penggunaan')])>Panduan</a>
            <a href="{{ url('/app/login') }}" class="rp-tombol rp-tombol-kecil">Masuk</a>
        </nav>
    </div>
</header>
<main class="rp-wadah rp">
    <h1>{{ $judul }}</h1>
    @include($isi, ['publik' => true])
</main>
<footer class="rp-kaki">
    <div class="rp-wadah">
        <img src="{{ asset('img/logo/ruci-notaria.svg') }}" alt="Ruci Notaria" width="150" height="32">
        <p>Produk <a href="https://rucisoft.id" style="color:inherit;text-decoration:underline">Rucisoft</a> &middot; <a href="https://rucisoft.id" style="color:inherit">rucisoft.id</a></p>
        <p>Aplikasi membantu kepatuhan, tetapi tidak menggantikan pertimbangan dan tanggung jawab notaris.@if (config('notaria.demo.enabled')) Gambar dan data pada situs ini adalah data contoh fiktif.@endif</p>
    </div>
</footer>
</body>
</html>
