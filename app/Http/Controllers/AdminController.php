<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    // Daftar admin dan form tambah/ubah (khusus owner)
    public function index(Request $request)
    {
        $admin = User::where('peran', 'admin')
            ->withCount('logAktivitas')
            ->orderBy('nama')
            ->get();

        $edit = $request->edit ? User::where('peran', 'admin')->find($request->edit) : null;

        return view('owner.admin', compact('admin', 'edit'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(), $this->pesan());
        $data['peran'] = 'admin';

        $admin = User::create($data);
        LogAktivitas::catat('Menambah admin', 'Admin: ' . $admin->nama);

        return redirect()->route('owner.admin.index')->with('sukses', 'Akun admin berhasil ditambahkan.');
    }

    public function update(Request $request, User $admin)
    {
        abort_if($admin->peran !== 'admin', 404);

        $data = $request->validate($this->aturan($admin), $this->pesan());
        if (empty($data['password'])) {
            unset($data['password']); // kata sandi tidak diubah jika dikosongkan
        }

        $admin->update($data);
        LogAktivitas::catat('Mengubah admin', 'Admin: ' . $admin->nama);

        return redirect()->route('owner.admin.index')->with('sukses', 'Data admin berhasil diperbarui.');
    }

    public function destroy(User $admin)
    {
        abort_if($admin->peran !== 'admin', 404);

        // Admin yang sudah punya riwayat aktivitas tidak dihapus agar jejaknya tetap ada
        if ($admin->logAktivitas()->exists()) {
            return back()->with('gagal', 'Admin tidak dapat dihapus karena sudah memiliki riwayat aktivitas.');
        }

        $nama = $admin->nama;
        $admin->delete();
        LogAktivitas::catat('Menghapus admin', 'Admin: ' . $nama);

        return redirect()->route('owner.admin.index')->with('sukses', 'Akun admin berhasil dihapus.');
    }

    private function aturan(?User $admin = null): array
    {
        return [
            'nama'     => 'required|string|max:50',
            'email'    => ['required', 'email', 'max:50', Rule::unique('users', 'email')->ignore($admin?->id)],
            'no_hp'    => 'required|string|max:15',
            'password' => [$admin ? 'nullable' : 'required', 'string', 'min:10', 'max:15'],
        ];
    }

    private function pesan(): array
    {
        return [
            'nama.required'     => 'Nama admin wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah digunakan oleh akun lain.',
            'no_hp.required'    => 'Nomor HP wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min'      => 'Panjang kata sandi tidak sesuai (10–15 karakter).',
            'password.max'      => 'Panjang kata sandi tidak sesuai (10–15 karakter).',
        ];
    }
}