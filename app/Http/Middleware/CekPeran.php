<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekPeran
{
    // Contoh pemakaian di route: peran:owner  atau  peran:owner,admin
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        // Jika peran tidak sesuai, kembalikan ke halaman miliknya sendiri
        if (! in_array($user->peran, $peran)) {
            return redirect(AuthController::halamanAwal($user))
                ->with('pesan', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}