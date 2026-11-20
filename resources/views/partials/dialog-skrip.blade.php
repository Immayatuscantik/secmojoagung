{{-- Skrip pembuka/penutup pop-up; disertakan sekali pada halaman yang memakai dialog-absensi --}}
@push('script')
<script>
    document.querySelectorAll('.buka-dialog').forEach(tombol => {
        tombol.addEventListener('click', () => document.getElementById(tombol.dataset.dialog).showModal());
    });
    document.querySelectorAll('.tutup-dialog').forEach(tombol => {
        tombol.addEventListener('click', () => tombol.closest('dialog').close());
    });
    // Klik di luar kotak pop-up untuk menutup
    document.querySelectorAll('dialog.dialog').forEach(d => {
        d.addEventListener('click', e => { if (e.target === d) d.close(); });
    });
    // Buka kembali pop-up yang gagal disimpan (misalnya catatan dikosongkan)
    document.querySelectorAll('dialog.buka-otomatis').forEach(d => d.showModal());
</script>
@endpush