<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Menampilkan halaman login
    public function showLogin()
    {
        return view('auth.login');
    }

    // Memproses login
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak sesuai.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Mencari akun berdasarkan email (where(email) pada sequence diagram)
        $user = User::where('email', $data['email'])->first();

        // Mencocokkan kata sandi dengan kata sandi terenkripsi di basis data
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['login' => 'Email atau kata sandi salah.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Mengarahkan pengguna sesuai perannya
        return redirect(self::halamanAwal($user));
    }

    // Menampilkan halaman pendaftaran akun wali murid
    public function showDaftar()
    {
        return view('auth.daftar');
    }

    // Memproses pendaftaran akun wali murid
    public function daftar(Request $request)
    {
        $data = $request->validate([
            'nama'      => 'required|string|max:50',
            'email'     => 'required|email|max:50|unique:users,email',
            'no_hp'     => 'required|string|max:15',
            'password'  => 'required|string|min:10|max:15|confirmed',
            'hubungan'  => 'required|in:Ayah,Ibu,Wali',
            'pekerjaan' => 'nullable|string|max:50',
            'alamat'    => 'required|string|max:255',
        ], [
            'nama.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar. Silakan gunakan email lain atau masuk.',
            'no_hp.required'     => 'Nomor HP wajib diisi.',
            'password.required'  => 'Kata sandi wajib diisi.',
            'password.min'       => 'Panjang kata sandi tidak sesuai (10–15 karakter).',
            'password.max'       => 'Panjang kata sandi tidak sesuai (10–15 karakter).',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'hubungan.required'  => 'Hubungan dengan murid wajib dipilih.',
            'alamat.required'    => 'Alamat wajib diisi.',
        ]);

        // Akun (users) dan profil wali (wali_murid) disimpan bersamaan
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'nama'     => $data['nama'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'no_hp'    => $data['no_hp'],
                'peran'    => 'wali',
            ]);

            $user->waliMurid()->create([
                'hubungan'  => $data['hubungan'],
                'pekerjaan' => $data['pekerjaan'] ?? null,
                'alamat'    => $data['alamat'],
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(self::halamanAwal($user))
            ->with('sukses', 'Pendaftaran berhasil. Selamat datang, ' . $user->nama . '.');
    }

    // Keluar dari sistem
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Halaman awal setiap peran
    /** @param \App\Models\User $user */
    public static function halamanAwal($user): string
    {
        return match ($user->peran) {
            'owner', 'admin' => route('owner.dashboard'),
            'mentor'         => route('mentor.job'),
            'wali'           => route('wali.anak'),
        };
    }
}