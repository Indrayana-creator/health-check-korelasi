import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Lonceng notifikasi topbar -- poll berkala biar count & daftarnya keupdate
// tanpa reload, dan filter Semua/Belum Dibaca dikerjakan di client karena
// datanya (maks 8 notifikasi terakhir) sudah ada di memori.
Alpine.data('notifBell', (initial) => ({
    items: initial.items,
    count: initial.count,
    filter: 'semua',
    pollUrl: initial.pollUrl,
    csrfToken: document.querySelector('meta[name="csrf-token"]').content,

    init() {
        setInterval(() => this.poll(), 30000);
    },

    poll() {
        fetch(this.pollUrl, { headers: { Accept: 'application/json' } })
            .then((res) => res.json())
            .then((data) => {
                this.count = data.count;
                this.items = data.items;
            })
            .catch(() => {});
    },

    get itemsTertampil() {
        return this.filter === 'belum'
            ? this.items.filter((n) => !n.read)
            : this.items;
    },
}));

Alpine.start();
