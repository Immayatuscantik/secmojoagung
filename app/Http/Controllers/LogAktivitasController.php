<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;

class LogAktivitasController extends Controller
{
    // Riwayat aktivitas owner dan admin (khusus owner)
    public function index(Request $request)
    {
        $request->validate([
            'pengguna' => 'nullable|integer',
            'tanggal'  => 'nullable|date',
        ]);

        $pengguna = User::whereIn('peran', ['owner', 'admin'])->orderBy('nama')->get();

        $log = LogAktivitas::with('user')
            ->when($request->pengguna, fn ($q, $id) => $q->where('id_user', $id))
            ->when($request->tanggal, fn ($q, $t) => $q->whereDate('created_at', $t))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('owner.log', compact('log', 'pengguna'));
    }
}