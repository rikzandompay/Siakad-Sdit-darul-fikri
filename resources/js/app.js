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

Alpine.start();
