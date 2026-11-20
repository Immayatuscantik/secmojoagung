{{-- Bidang yang dikuasai mentor; dipakai di Detail Mentor dan Profil Saya --}}
<section class="panel">
    <h2>Bidang yang dikuasai</h2>
    <table class="tabel">
        <thead>
            <tr><th>Bidang</th><th>Tingkat yang diajar</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @forelse ($mentor->bidang as $b)
                <tr>
                    <td><strong>{{ $b->nama_bidang }}</strong></td>
                    <td>
                        @foreach ($mentor->tingkatUntuk($b->id) as $t)
                            <span class="label">{{ $t }}</span>
                        @endforeach
                    </td>
                    <td>{{ $b->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="kosong">Belum ada bidang.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="bantuan jarak-atas-kiri">Daftar Job mentor hanya menampilkan kursus pada bidang dan tingkat ini.</p>
</section>