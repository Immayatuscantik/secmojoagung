<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BidangController extends Controller
{
    // Daftar bidang/kursus dan form tambah/ubah
    public function index(Request $request)
    {
        // Jumlah mentor dihitung unik (satu mentor bisa punya beberapa tingkat pada bidang yang sama)
        $bidang = Bidang::with('tarifHonor')
            ->withCount(['kursus', 'mentor' => fn ($q) => $q->select(DB::raw('count(distinct mentor.id)'))])
            ->orderBy('nama_bidang')
            ->get();
        $edit = $request->edit ? Bidang::find($request->edit) : null;

        return view('owner.bidang', compact('bidang', 'edit'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(), $this->pesan());

        $bidang = Bidang::create($data);
        LogAktivitas::catat('Menambah bidang', 'Bidang: ' . $bidang->nama_bidang);

        return redirect()->route('owner.bidang.index')->with('sukses', 'Bidang berhasil ditambahkan.');
    }

    public function update(Request $request, Bidang $bidang)
    {
        $data = $request->validate($this->aturan($bidang), $this->pesan());

        $lama = $bidang->nama_bidang;
        $bidang->update($data);
        LogAktivitas::catat('Mengubah bidang', $lama === $bidang->nama_bidang
            ? 'Bidang: ' . $bidang->nama_bidang
            : "Bidang: {$lama} menjadi {$bidang->nama_bidang}");

        return redirect()->route('owner.bidang.index')->with('sukses', 'Bidang berhasil diperbarui.');
    }

    public function destroy(Bidang $bidang)
    {
        // Bidang yang sudah dipakai mentor atau kursus tidak boleh dihapus
        if ($bidang->mentor()->exists() || $bidang->kursus()->exists()) {
            return back()->with('gagal', 'Bidang tidak dapat dihapus karena sudah dipakai oleh mentor atau kursus.');
        }

        $nama = $bidang->nama_bidang;
        $bidang->delete();
        LogAktivitas::catat('Menghapus bidang', 'Bidang: ' . $nama);

        return redirect()->route('owner.bidang.index')->with('sukses', 'Bidang berhasil dihapus.');
    }

    private function aturan(?Bidang $bidang = null): array
    {
        return [
            'nama_bidang' => ['required', 'string', 'max:50', Rule::unique('bidang', 'nama_bidang')->ignore($bidang?->id)],
            'keterangan'  => 'nullable|string|max:255',
        ];
    }

    private function pesan(): array
    {
        return [
            'nama_bidang.required' => 'Nama bidang wajib diisi.',
            'nama_bidang.max'      => 'Nama bidang maksimal 50 karakter.',
            'nama_bidang.unique'   => 'Nama bidang sudah ada.',
            'keterangan.max'       => 'Keterangan maksimal 255 karakter.',
        ];
    }
}