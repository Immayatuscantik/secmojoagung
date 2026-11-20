{{-- Kerangka bersama untuk halaman owner/admin, mentor, dan wali.
     Layout per peran mengisi: @section('peran') dan @section('menu') --}}
@php
    $pengguna = auth()->user();
    $inisial = collect(explode(' ', trim($pengguna->nama)))->filter()->take(2)
        ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))->implode('');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul') - SEC Mojoagung</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="tata-letak min-h-screen lg:flex">

    {{-- Bilah atas (hanya di layar kecil) --}}
    <header class="bilah-atas sticky top-0 z-20 flex items-center justify-between border-b border-garis bg-white/90 px-4 py-3 backdrop-blur lg:hidden">
        <div class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="" class="size-8 rounded-full bg-white">
            <span class="font-semibold text-tinta">SEC Mojoagung</span>
        </div>
        <button type="button" id="buka-menu" class="rounded-lg p-2 text-tinta hover:bg-latar" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
            <x-ikon nama="menu" class="size-6" />
        </button>
    </header>

    <div id="tirai" class="fixed inset-0 z-30 hidden bg-tinta/50 lg:hidden"></div>

    {{-- Menu samping --}}
    <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-40 flex w-68 -translate-x-full flex-col bg-tinta text-white transition-transform duration-200
                               lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0">
        <div class="flex items-center gap-3 px-5 pt-6 pb-5">
            <img src="{{ asset('images/logo.png') }}" alt="Logo SEC Mojoagung" class="size-11 rounded-full bg-white p-0.5">
            <div class="leading-tight">
                <p class="font-bold">SEC Mojoagung</p>
                <p class="text-xs text-white/60">Smart Effective Course</p>
            </div>
            <button type="button" id="tutup-menu" class="ml-auto rounded-lg p-1.5 text-white/70 hover:bg-tinta-muda lg:hidden" aria-label="Tutup menu">
                <x-ikon nama="tutup" />
            </button>
        </div>

        <div class="mx-4 mb-4 flex items-center gap-3 rounded-xl bg-white/[.06] px-3 py-3 ring-1 ring-white/10">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-kuning text-sm font-bold text-tinta">{{ $inisial }}</span>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-semibold">{{ $pengguna->nama }}</p>
                <p class="text-xs text-white/60">@yield('peran')</p>
            </div>
        </div>

        <nav class="menu flex min-h-0 flex-1 flex-col gap-0.5 overflow-y-auto px-3 pb-4">
            @yield('menu')
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10 p-3">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/75 transition-colors hover:bg-merah/25 hover:text-white">
                <x-ikon nama="keluar" />
                Keluar
            </button>
        </form>
    </aside>

    {{-- Isi halaman --}}
    <main class="konten">
        @if (session('sukses')) <div class="notif sukses">{{ session('sukses') }}</div> @endif
        @if (session('gagal'))  <div class="notif gagal">{{ session('gagal') }}</div>  @endif
        @if (session('pesan'))  <div class="notif gagal">{{ session('pesan') }}</div>  @endif

        @yield('isi')
    </main>

    @stack('script')
</body>
</html>