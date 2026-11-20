{{-- Kerangka halaman Masuk dan Daftar --}}
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
</head>
<body class="min-h-screen lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">

    <section class="relative flex flex-col gap-8 overflow-hidden bg-tinta px-6 py-8 text-white sm:px-10 lg:min-h-screen lg:px-14 lg:py-12">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Logo SEC Mojoagung" class="size-14 rounded-full bg-white p-0.5 shadow-lg shadow-black/20">
            <div class="leading-tight">
                <p class="font-bold">SEC Smart Effective Course</p>
                <p class="text-sm text-white/60">Les privat · Mojoagung, Jombang</p>
            </div>
        </div>

        <div class="max-w-md lg:my-auto">
            <h1 class="text-3xl leading-tight font-bold text-white sm:text-4xl">@yield('sambutan-judul')</h1>
            <div class="mt-5 h-1.5 w-16 rounded-full bg-kuning"></div>
            <p class="mt-5 leading-relaxed text-white/75">@yield('sambutan-teks')</p>
        </div>

        <ul class="hidden max-w-md gap-3 text-sm text-white/80 lg:grid">
            <li class="flex items-center gap-3"><span class="grid size-8 place-items-center rounded-lg bg-white/10 text-kuning"><x-ikon nama="absensi" class="size-4" /></span>Absensi mentor dengan lokasi dan swafoto</li>
            <li class="flex items-center gap-3"><span class="grid size-8 place-items-center rounded-lg bg-white/10 text-kuning"><x-ikon nama="murid" class="size-4" /></span>Perkembangan belajar murid tiap pertemuan</li>
            <li class="flex items-center gap-3"><span class="grid size-8 place-items-center rounded-lg bg-white/10 text-kuning"><x-ikon nama="honor" class="size-4" /></span>Pengajuan dan pencairan honor mentor</li>
        </ul>

        {{-- Hiasan: tiga kartu kuning seperti pada logo SEC --}}
        <div class="pointer-events-none absolute -right-10 -bottom-12 hidden gap-3 opacity-[.08] lg:flex" aria-hidden="true">
            <span class="h-48 w-28 -rotate-12 rounded-xl bg-kuning"></span>
            <span class="h-48 w-28 rounded-xl bg-kuning"></span>
            <span class="h-48 w-28 rotate-12 rounded-xl bg-kuning"></span>
        </div>
    </section>

    <section class="flex items-center justify-center px-4 py-10 sm:px-8 lg:overflow-y-auto">
        <div class="w-full max-w-[420px]">
            @yield('isi')
        </div>
    </section>
</body>
</html>