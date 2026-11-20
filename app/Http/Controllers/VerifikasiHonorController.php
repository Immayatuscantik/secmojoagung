<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pencairan;
use App\Models\PengajuanHonor;
use App\Support\Rupiah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifikasiHonorController extends Controller
{
    const STATUS = ['Diajukan', 'Dikembalikan', 'Disetujui', 'Dicairkan'];

    // Daftar pengajuan honor
    public function index(Request $request)
    {
        $status = in_array($request->status, self::STATUS) ? $request->status : null;

        $jumlah = PengajuanHonor::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pengajuan = PengajuanHonor::with(['mentor.user', 'kursus.murid', 'kursus.bidang'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->latest('tanggal_pengajuan')
            ->latest('id')
            ->get();

        return view('owner.honor.index', compact('pengajuan', 'jumlah', 'status'));
    }

    // Detail pengajuan: rekap absensi, verifikasi wali, video, dan tindakan
    public function show(PengajuanHonor $pengajuan)
    {
        $pengajuan->load([
            'mentor.user',
            'kursus.murid.wali.user',
            'kursus.bidang',
            'kursus.jadwal' => fn ($q) => $q->orderBy('pertemuan_ke'),
            'kursus.jadwal.absensi',
            'pencairan',
        ]);

        // Mengubah link Drive menjadi link pratinjau agar video bisa diputar di halaman
        preg_match('#/file/d/([\w-]+)#', $pengajuan->video, $cocok);
        $embed = isset($cocok[1]) ? "https://drive.google.com/file/d/{$cocok[1]}/preview" : null;

        return view('owner.honor.show', compact('pengajuan', 'embed'));
    }

    // Menyetujui pengajuan
    public function setujui(PengajuanHonor $pengajuan)
    {
        if ($pengajuan->status !== 'Diajukan') {
            return back()->with('gagal', 'Pengajuan ini tidak berstatus Diajukan.');
        }

        $pengajuan->update(['status' => 'Disetujui']);
        LogAktivitas::catat('Menyetujui pengajuan honor', $this->uraian($pengajuan));

        return back()->with('sukses', 'Pengajuan honor disetujui. Silakan lakukan pencairan.');
    }

    // Mengembalikan pengajuan kepada mentor (R10)
    public function kembalikan(Request $request, PengajuanHonor $pengajuan)
    {
        if ($pengajuan->status !== 'Diajukan') {
            return back()->with('gagal', 'Pengajuan ini tidak berstatus Diajukan.');
        }

        $data = $request->validate([
            'alasan_pengembalian' => 'required|string|max:255',
        ], [
            'alasan_pengembalian.required' => 'Alasan pengembalian wajib diisi.',
        ]);

        $pengajuan->update([
            'status'              => 'Dikembalikan',
            'alasan_pengembalian' => $data['alasan_pengembalian'],
        ]);
        LogAktivitas::catat('Mengembalikan pengajuan honor', $this->uraian($pengajuan) . '. Alasan: ' . $data['alasan_pengembalian']);

        return redirect()->route('owner.honor')->with('sukses', 'Pengajuan honor dikembalikan kepada mentor.');
    }

    // Pencairan honor (R11); nominal diambil otomatis dari honor kursus
    public function cairkan(Request $request, PengajuanHonor $pengajuan)
    {
        // R11: pencairan hanya untuk pengajuan yang sudah disetujui
        if ($pengajuan->status !== 'Disetujui') {
            return back()->with('gagal', 'Pencairan hanya dapat dilakukan untuk pengajuan yang sudah disetujui.');
        }

        $data = $request->validate([
            'tanggal_pencairan' => 'required|date|before_or_equal:today',
            'bukti_transfer'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'tanggal_pencairan.required'        => 'Tanggal pencairan wajib diisi.',
            'tanggal_pencairan.before_or_equal' => 'Tanggal pencairan tidak boleh melebihi hari ini.',
            'bukti_transfer.required'           => 'Bukti transfer wajib diunggah.',
            'bukti_transfer.mimes'              => 'Bukti transfer harus berupa JPG, PNG, atau PDF.',
            'bukti_transfer.max'                => 'Ukuran bukti transfer maksimal 2 MB.',
        ]);

        $path = $request->file('bukti_transfer')->store('bukti', 'public');

        DB::transaction(function () use ($pengajuan, $data, $path) {
            Pencairan::create([
                'id_pengajuan'      => $pengajuan->id,
                'tanggal_pencairan' => $data['tanggal_pencairan'],
                'nominal'           => $pengajuan->kursus->honor,
                'bukti_transfer'    => $path,
            ]);
            $pengajuan->update(['status' => 'Dicairkan']);
        });

        LogAktivitas::catat('Mencairkan honor', $this->uraian($pengajuan) . ' sebesar ' . Rupiah::format($pengajuan->kursus->honor));

        return back()->with('sukses', 'Honor sebesar ' . Rupiah::format($pengajuan->kursus->honor) . ' berhasil dicairkan.');
    }

    private function uraian(PengajuanHonor $p): string
    {
        $p->loadMissing(['mentor.user', 'kursus.murid', 'kursus.bidang']);

        return "Mentor {$p->mentor->user->nama} - {$p->kursus->bidang->nama_bidang} {$p->kursus->murid->nama}";
    }
}
