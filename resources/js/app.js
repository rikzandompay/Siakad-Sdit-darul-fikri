// Alpine "CSP build": tidak memakai eval/new Function, jadi cocok dengan
// Content-Security-Policy tanpa 'unsafe-eval' (lihat SecurityHeaders).
// Karena itu, atribut x-* di Blade hanya boleh memanggil properti/method
// dari komponen di bawah, bukan ekspresi inline.
import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

// Kerangka halaman: sidebar versi mobile.
Alpine.data('shell', () => ({
    sidebarOpen: false,

    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
    },

    get shiftClass() {
        return this.sidebarOpen ? 'max-md:translate-x-64' : '';
    },

    get asideClass() {
        return this.sidebarOpen ? 'max-md:translate-x-0' : '';
    },
}));

// Menu dropdown di sidebar. Status awal dibaca dari data-open="true|false".
Alpine.data('menuGroup', () => ({
    open: false,

    init() {
        this.open = this.$el.dataset.open === 'true';
    },

    toggle() {
        this.open = !this.open;
    },

    get chevronClass() {
        return this.open ? 'rotate-180' : '';
    },
}));

// Agenda jadwal mengajar: tab hari. Hari awal dibaca dari data-awal; tiap tab dan panel membawa data-hari.
Alpine.data('agendaHari', () => ({
    hari: '',

    init() {
        this.hari = this.$el.dataset.awal;
    },

    pilih() {
        this.hari = this.$el.dataset.hari;
    },

    get dipilih() {
        return this.$el.dataset.hari === this.hari;
    },

    get kelasTab() {
        return this.dipilih ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200';
    },

    get kelasHitungan() {
        return this.dipilih ? 'text-blue-100' : 'text-gray-600';
    },
}));

Alpine.start();
