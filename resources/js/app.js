import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Kompres foto sebelum diupload -- kamera HP modern bisa hasilin foto 5-15MB
// sekali jepret, jauh lebih besar dari yang perlu buat dokumentasi. Di-resize
// & di-encode ulang jadi JPEG di browser (Canvas) sebelum submit, biar upload
// tetap ringan & cepat walau originalnya gede, TANPA nolak foto asli user
// kayak validasi ukuran server yang kaku/instan gagal.
window.kompresFoto = function (file, maxDimensi = 1600, kualitas = 0.8) {
    return new Promise((resolve) => {
        if (!file.type.startsWith('image/')) {
            resolve(file);
            return;
        }
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            let { width, height } = img;
            if (width > maxDimensi || height > maxDimensi) {
                if (width > height) {
                    height = Math.round((height * maxDimensi) / width);
                    width = maxDimensi;
                } else {
                    width = Math.round((width * maxDimensi) / height);
                    height = maxDimensi;
                }
            }
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);
            canvas.toBlob((blob) => {
                URL.revokeObjectURL(url);
                if (!blob) {
                    resolve(file);
                    return;
                }
                resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' }));
            }, 'image/jpeg', kualitas);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(file);
        };
        img.src = url;
    });
};

// Wrapper kompresFoto() buat input file POLOS (bukan yang punya state Alpine
// custom kayak Dokumentasi Visual) -- kompres semua file yang lagi dipilih di
// situ, terus timpa balik input.files-nya (lewat DataTransfer) biar yang
// kekirim pas submit udah versi terkompresi, transparan tanpa ubah UI. Dipakai
// buat foto bukti item checklist & foto Lapor Kerusakan, biar sama-sama gak
// nolak foto asli kamera HP kayak Dokumentasi Visual.
window.kompresFotoInput = async function (inputEl) {
    if (!inputEl.files || inputEl.files.length === 0) return;
    const dt = new DataTransfer();
    for (const file of inputEl.files) {
        dt.items.add(await window.kompresFoto(file));
    }
    inputEl.files = dt.files;
};

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
