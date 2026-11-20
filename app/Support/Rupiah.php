<?php

namespace App\Support;

class Rupiah
{
    // Contoh: Rupiah::format(150000) -> "Rp150.000"
    public static function format(?int $nominal): string
    {
        return 'Rp' . number_format($nominal ?? 0, 0, ',', '.');
    }
}