<?php

// Identitas lembaga untuk kop surat laporan cetak.
// Ubah isinya langsung di sini, atau lewat file .env (SEC_ALAMAT, SEC_TELEPON, SEC_EMAIL).
return [
    'nama'     => env('SEC_NAMA', 'BIMBEL PRIVAT SEC MOJOAGUNG'),
    'tagline'  => env('SEC_TAGLINE', 'Smart Effective Course'),
    'alamat'   => env('SEC_ALAMAT', 'Mojoagung, Kabupaten Jombang, Jawa Timur'),
    'telepon'  => env('SEC_TELEPON', ''),
    'email'    => env('SEC_EMAIL', ''),
    'kota'     => env('SEC_KOTA', 'Mojoagung'),
    'penanda'  => env('SEC_PENANDA', 'Owner Bimbel Privat SEC Mojoagung'),
];
