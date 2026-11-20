{{-- Ikon garis sederhana. Pemakaian: <x-ikon nama="beranda" class="size-5" /> --}}
@props(['nama'])
@php
    $ikon = [
        'beranda'    => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'mentor'     => '<circle cx="12" cy="7.5" r="3.5"/><path d="M5 20.5a7 7 0 0 1 14 0"/><path d="M9.5 14.5 12 17l2.5-2.5"/>',
        'bidang'     => '<path d="M4 19.5V5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2v0a1.5 1.5 0 0 0 1.5 1.5H19"/><path d="M8 7h7M8 10.5h5"/>',
        'tarif'      => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5v5M18 9.5v5"/>',
        'murid'      => '<path d="m2 9.5 10-5 10 5-10 5z"/><path d="M6 11.5V16c0 1.4 2.7 3 6 3s6-1.6 6-3v-4.5"/><path d="M22 9.5V15"/>',
        'honor'      => '<path d="M6 2.5h12v19l-3-2-3 2-3-2-3 2z"/><path d="M9 7.5h6M9 11h6M9 14.5h3.5"/>',
        'laporan'    => '<path d="M3 20.5h18"/><path d="M6 16.5v-5M10.5 16.5v-9M15 16.5v-6.5M19.5 16.5V5"/>',
        'admin'      => '<path d="M12 2.5 19.5 5.5v6c0 4.6-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.9-7.5-9.5v-6z"/><path d="m9 12 2 2 4-4"/>',
        'aktivitas'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'job'        => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8.5 7V5.5a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2V7"/><path d="M3 12.5h18"/>',
        'absensi'    => '<path d="M12 21.5s-7-6-7-11.5a7 7 0 0 1 14 0c0 5.5-7 11.5-7 11.5z"/><path d="m9.5 10 1.8 1.8L14.5 8.5"/>',
        'portofolio' => '<path d="M3 7.5a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="m12 10.5.9 1.9 2 .3-1.5 1.4.4 2-1.8-1-1.8 1 .4-2-1.5-1.4 2-.3z"/>',
        'kalender'   => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/><path d="m9.5 15 2 2 3.5-3.5"/>',
        'profil'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'anak'       => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17.5" cy="9.5" r="2.5"/><path d="M16.5 14.6a5 5 0 0 1 5.5 5"/>',
        'keluar'     => '<path d="M14.5 4H18a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3.5"/><path d="m10 16-4-4 4-4"/><path d="M6 12h10"/>',
        'menu'       => '<path d="M4 6.5h16M4 12h16M4 17.5h16"/>',
        'tutup'      => '<path d="m6 6 12 12M18 6 6 18"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ikon[$nama] ?? '' !!}</svg>
