/*
 * Mencari koordinat dari teks alamat menggunakan layanan Nominatim (OpenStreetMap).
 * Jika alamat lengkap tidak ditemukan, bagian paling depan (misalnya nama jalan/nomor rumah)
 * dibuang satu per satu sampai ditemukan, misalnya:
 *   "Jl. Melati No. 5, Kademangan, Mojoagung, Jombang"
 *   -> "Kademangan, Mojoagung, Jombang" -> "Mojoagung, Jombang" -> ...
 *
 * Hasil: { lat, lng, nama, tepat } atau null jika tidak ditemukan.
 *   tepat = true jika alamat lengkap langsung ditemukan.
 */
async function cariAlamat(teksAlamat) {
    const bagian = teksAlamat.split(',').map(s => s.trim()).filter(s => s !== '');
    const jumlahAwal = bagian.length;

    while (bagian.length > 0) {
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id'
            + '&accept-language=id&q=' + encodeURIComponent(bagian.join(', '));

        const respon = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!respon.ok) {
            throw new Error('Layanan peta tidak dapat dihubungi.');
        }

        const hasil = await respon.json();
        if (hasil.length > 0) {
            return {
                lat: parseFloat(hasil[0].lat),
                lng: parseFloat(hasil[0].lon),
                nama: hasil[0].display_name,
                tepat: bagian.length === jumlahAwal,
            };
        }

        bagian.shift();
        // Aturan pemakaian Nominatim: maksimal 1 permintaan per detik
        await new Promise(selesai => setTimeout(selesai, 1100));
    }

    return null;
}

/*
 * Menghubungkan tombol "Cari di Peta" dengan isian alamat dan peta Leaflet.
 *   tombol    : elemen tombol
 *   isian     : elemen textarea alamat
 *   peta      : objek peta Leaflet
 *   setTitik  : fungsi pemasang penanda, menerima L.latLng
 *   teksInfo  : elemen untuk menampilkan pesan
 */
function hubungkanCariAlamat(tombol, isian, peta, setTitik, teksInfo) {
    tombol.addEventListener('click', async () => {
        const alamat = isian.value.trim();
        if (alamat === '') {
            teksInfo.textContent = 'Isi alamat terlebih dahulu.';
            return;
        }

        tombol.disabled = true;
        tombol.textContent = 'Mencari...';
        teksInfo.textContent = 'Mencari lokasi alamat di peta...';

        try {
            const hasil = await cariAlamat(alamat);
            if (hasil) {
                const titik = L.latLng(hasil.lat, hasil.lng);
                peta.setView(titik, hasil.tepat ? 17 : 15);
                setTitik(titik);
                teksInfo.textContent = (hasil.tepat ? 'Lokasi ditemukan: ' : 'Lokasi terdekat yang ditemukan: ')
                    + hasil.nama + '. Geser penanda jika belum tepat di rumah.';
            } else {
                teksInfo.textContent = 'Alamat tidak ditemukan di peta. Silakan klik lokasi rumah secara manual.';
            }
        } catch (e) {
            teksInfo.textContent = 'Pencarian gagal (periksa koneksi internet). Silakan klik lokasi rumah secara manual.';
        }

        tombol.disabled = false;
        tombol.textContent = 'Cari di Peta';
    });
}