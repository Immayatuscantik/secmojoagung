// Menu samping pada layar kecil (ponsel/tablet)
const sidebar = document.getElementById('sidebar');
const tirai = document.getElementById('tirai');
const tombolBuka = document.getElementById('buka-menu');

function aturMenu(terbuka) {
    if (!sidebar) return;
    sidebar.classList.toggle('-translate-x-full', !terbuka);
    tirai?.classList.toggle('hidden', !terbuka);
    tombolBuka?.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
}

tombolBuka?.addEventListener('click', () => aturMenu(true));
document.getElementById('tutup-menu')?.addEventListener('click', () => aturMenu(false));
tirai?.addEventListener('click', () => aturMenu(false));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') aturMenu(false); });