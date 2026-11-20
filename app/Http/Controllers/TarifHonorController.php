<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\LogAktivitas;
use App\Models\TarifHonor;
use App\Support\Rupiah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TarifHonorController extends Controller
{
    // Daftar tarif honor mentor dan form tambah/ubah
    public function index(Request $request)
    {
        $tarif = TarifHonor::with('bidang')
            ->get()
            ->sortBy(fn ($t) => [$t->bidang->nama_bidang, array_search($t->tingkat, TarifHonor::TINGKAT), $t->jumlah_pertemuan]);

        return view('owner.tarif', [
            'tarif'   => $tarif,
            'bidang'  => Bidang::orderBy('nama_bidang')->get(),
            'tingkat' => TarifHonor::TINGKAT,
            'edit'    => $request->edit ? TarifHonor::find($request->edit) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $tarif = TarifHonor::create($data);
        LogAktivitas::catat('Menambah tarif honor', $this->uraian($tarif));

        return redirect()->route('owner.tarif.index')->with('sukses', 'Tarif honor berhasil ditambahkan.');
    }

    public function update(Request $request, TarifHonor $tarif)
    {
        $data = $this->validasi($request, $tarif);

        $lama = Rupiah::format($tarif->nominal);
        $tarif->update($data);
        LogAktivitas::catat('Mengubah tarif honor', $this->uraian($tarif) . " (sebelumnya {$lama})");

        return redirect()->route('owner.tarif.index')->with('sukses', 'Tarif honor berhasil diperbarui.');
    }

    public function destroy(TarifHonor $tarif)
    {
        $uraian = $this->uraian($tarif);
        $tarif->delete();
        LogAktivitas::catat('Menghapus tarif honor', $uraian);

        return redirect()->route('owner.tarif.index')->with('sukses', 'Tarif honor berhasil dihapus.');
    }

    private function validasi(Request $request, ?TarifHonor $tarif = null): array
    {
        $data = $request->validate([
            'id_bidang'        => 'required|exists:bidang,id',
            'tingkat'          => ['required', Rule::in(TarifHonor::TINGKAT)],
            'jenis'            => 'required|in:paket,per_pertemuan',
            'jumlah_pertemuan' => 'nullable|required_if:jenis,paket|integer|min:1|max:50',
            'nominal'          => 'required|integer|min:1000',
        ], [
            'id_bidang.required'           => 'Bidang wajib dipilih.',
            'tingkat.required'             => 'Tingkat wajib dipilih.',
            'jenis.required'               => 'Jenis tarif wajib dipilih.',
            'jumlah_pertemuan.required_if' => 'Jumlah pertemuan wajib diisi untuk tarif paket.',
            'nominal.required'             => 'Nominal honor wajib diisi.',
            'nominal.integer'              => 'Nominal honor harus berupa angka tanpa titik.',
            'nominal.min'                  => 'Nominal honor minimal Rp1.000.',
        ]);

        if ($data['jenis'] === 'per_pertemuan') {
            $data['jumlah_pertemuan'] = null;
        }

        // Satu bidang + tingkat hanya boleh memakai satu jenis tarif, dan tidak boleh ada tarif ganda
        $lain = TarifHonor::where('id_bidang', $data['id_bidang'])
            ->where('tingkat', $data['tingkat'])
            ->when($tarif, fn ($q) => $q->where('id', '!=', $tarif->id))
            ->get();

        if ($lain->where('jenis', '!=', $data['jenis'])->isNotEmpty()) {
            throw ValidationException::withMessages(['jenis' => 'Bidang dan tingkat ini sudah memakai jenis tarif yang lain.']);
        }
        if ($data['jenis'] === 'per_pertemuan' && $lain->isNotEmpty()) {
            throw ValidationException::withMessages(['jenis' => 'Tarif per pertemuan untuk bidang dan tingkat ini sudah ada.']);
        }
        if ($data['jenis'] === 'paket' && $lain->firstWhere('jumlah_pertemuan', (int) $data['jumlah_pertemuan'])) {
            throw ValidationException::withMessages(['jumlah_pertemuan' => 'Paket dengan jumlah pertemuan ini sudah ada.']);
        }

        return $data;
    }

    private function uraian(TarifHonor $t): string
    {
        $t->loadMissing('bidang');

        return "{$t->bidang->nama_bidang} {$t->tingkat}, {$t->keterangan()}: " . Rupiah::format($t->nominal);
    }
}