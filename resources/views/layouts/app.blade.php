<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-800">
        <div class="min-h-screen flex bg-gray-50" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

            {{-- Backdrop, mobile only --}}
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            ></div>

            {{-- Sidebar --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 w-64 flex-none bg-gradient-to-b from-nusantara to-slate-900 text-white flex flex-col px-4 py-6 gap-6 h-screen overflow-y-auto transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:translate-x-0"
            >
                <div class="flex items-center justify-between gap-2.5 px-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                        <span class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center flex-none">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="text-base font-extrabold tracking-tight">AsetSehat</span>
                        <img
                            src="{{ asset('images/bri-logo-putih.png') }}"
                            alt="Bank Rakyat Indonesia"
                            class="h-5 w-auto object-contain flex-none"
                        >
                    </a>
                    <button @click="sidebarOpen = false" class="w-8 h-8 flex-none rounded-lg flex items-center justify-center text-white/70 hover:bg-white/10 lg:hidden">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                </div>

                <nav class="flex flex-col gap-1">
                    @php
                        // Dikelompokkan per "apa yang dikerjakan user", bukan sekadar
                        // view vs admin -- Monitoring Kendala & Permintaan Edit Aset
                        // dulu nyasar di grup Laporan/Administrasi padahal keduanya
                        // antrian kerja yang butuh tindakan, sama kayak Health Check
                        // & Permintaan Perangkat, jadi disatukan di sini.
                        $navMain = [
                            ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
                            ['route' => 'aset.index', 'pattern' => 'aset.*', 'label' => 'Data Aset', 'icon' => 'M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8'],
                        ];
                        if (auth()->user()->role === 'admin') {
                            $navMain[] = ['route' => 'aset.editRequests.index', 'pattern' => 'aset.editRequests.*', 'label' => 'Permintaan Edit Aset', 'icon' => 'M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z'];
                        }
                        $navMain[] = ['route' => 'healthcheck.index', 'pattern' => 'healthcheck.*', 'label' => 'Health Check', 'icon' => 'M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'];
                        $navMain[] = ['route' => 'monitoring.index', 'pattern' => 'monitoring.*', 'label' => 'Monitoring Kendala', 'icon' => 'M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'];
                        $navMain[] = ['route' => 'permintaan-perangkat.index', 'pattern' => 'permintaan-perangkat.*', 'label' => 'Permintaan Perangkat', 'icon' => 'M20 7h-9M14 17H5M17 3l3 4-3 4M7 21l-3-4 3-4'];
                    @endphp
                    @foreach ($navMain as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs($item['pattern']) ? 'bg-white/20' : 'hover:bg-white/10' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] flex-none opacity-95"><path d="{{ $item['icon'] }}"></path></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                {{-- Struktur Organisasi -- semua role bisa akses (admin lihat semua
                     dari Kanwil, user cuma lihat cabang sendiri + turunannya), jadi
                     grup "Laporan" ini gak lagi digabung ke dalam blok admin-only. --}}
                <div class="flex flex-col gap-1">
                    <p class="px-3 mb-1 text-[10.5px] font-bold uppercase tracking-wider text-white/50">Laporan</p>
                    @php
                        $navLaporan = auth()->user()->role === 'admin'
                            ? [
                                ['route' => 'rekap.cabang', 'pattern' => 'rekap.*', 'label' => 'Rekap Cabang', 'icon' => 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
                                ['route' => 'uker-tree.index', 'pattern' => 'uker-tree.index', 'label' => 'Struktur Organisasi', 'icon' => 'M6 3v12M18 9a3 3 0 100-6 3 3 0 000 6zM6 21a3 3 0 100-6 3 3 0 000 6zM15 6a9 9 0 01-9 9'],
                                ['route' => 'rekap.permintaanPerangkat', 'pattern' => 'rekap.permintaanPerangkat', 'label' => 'Rekap Permintaan Perangkat', 'icon' => 'M20 7h-9M14 17H5M17 3l3 4-3 4M7 21l-3-4 3-4'],
                            ]
                            : [
                                ['route' => 'uker-tree.index', 'pattern' => 'uker-tree.index', 'label' => 'Struktur Organisasi', 'icon' => 'M6 3v12M18 9a3 3 0 100-6 3 3 0 000 6zM6 21a3 3 0 100-6 3 3 0 000 6zM15 6a9 9 0 01-9 9'],
                            ];
                    @endphp
                    @foreach ($navLaporan as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs($item['pattern']) ? 'bg-white/20' : 'hover:bg-white/10' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] flex-none opacity-95"><path d="{{ $item['icon'] }}"></path></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>

                @if (auth()->user()->role === 'admin')
                    <div class="flex flex-col gap-1">
                        <p class="px-3 mb-1 text-[10.5px] font-bold uppercase tracking-wider text-white/50">Administrasi</p>
                        @php
                            $navAdmin = [
                                ['route' => 'users.index', 'pattern' => 'users.*', 'label' => 'Kelola User', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75'],
                                ['route' => 'ukers.index', 'pattern' => 'ukers.*', 'label' => 'Kelola Uker', 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1M9 21v-4h6v4'],
                                ['route' => 'pekerja.index', 'pattern' => 'pekerja.*', 'label' => 'Kelola Pekerja', 'icon' => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z'],
                                ['route' => 'kode-aset.index', 'pattern' => 'kode-aset.*', 'label' => 'Kelola Kode Aset', 'icon' => 'M20.59 13.41L11 3.83V3H3v8h.83l9.58 9.59a2 2 0 002.83 0l4.35-4.35a2 2 0 000-2.83zM6.5 7.5a1 1 0 110-2 1 1 0 010 2z'],
                                ['route' => 'log-history.index', 'pattern' => 'log-history.*', 'label' => 'Log History', 'icon' => 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 6v6l4 2'],
                            ];
                        @endphp
                        @foreach ($navAdmin as $item)
                            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs($item['pattern']) ? 'bg-white/20' : 'hover:bg-white/10' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] flex-none opacity-95"><path d="{{ $item['icon'] }}"></path></svg>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="mt-auto flex flex-col gap-3">
                    <div class="h-px bg-white/15"></div>
                    <div class="flex items-center gap-2.5 px-2">
                        <div class="w-8 h-8 rounded-full bg-mentari text-nusantara flex items-center justify-center font-extrabold text-xs flex-none">
                            {{ collect(explode(' ', auth()->user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-white/65">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Petugas Cabang' }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg border border-white/20 text-white/85 text-xs font-semibold hover:bg-white/10">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"></path></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main --}}
            <div class="flex-1 min-w-0 flex flex-col">
                <header class="h-16 flex-none flex items-center justify-between gap-4 px-4 sm:px-7 border-b border-gray-200 bg-white sticky top-0 z-10">
                    <div class="min-w-0 flex items-center gap-3">
                        <button @click="sidebarOpen = true" class="w-9 h-9 flex-none rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50 lg:hidden">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <div class="min-w-0">
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>
                    </div>

                    <div
                        class="flex-1 max-w-sm mx-auto hidden sm:block relative"
                        x-data="{
                            q: '', open: false, loading: false, results: null,
                            async cari() {
                                if (this.q.trim().length < 2) { this.results = null; this.open = false; return; }
                                this.loading = true;
                                try {
                                    const res = await fetch(`{{ route('search.api') }}?q=${encodeURIComponent(this.q)}`);
                                    this.results = await res.json();
                                    this.open = true;
                                } catch (e) { this.results = null; }
                                this.loading = false;
                            }
                        }"
                        @click.outside="open = false"
                    >
                        <div class="relative">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg>
                            <input
                                type="text"
                                x-model="q"
                                x-on:input.debounce.300ms="cari()"
                                x-on:focus="if (results) open = true"
                                placeholder="Cari aset, SN, periode, cabang..."
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-cakrawala focus:ring-cakrawala"
                            >
                        </div>

                        <div x-show="open" x-cloak class="absolute mt-1.5 w-full bg-white border border-gray-200 rounded-xl shadow-lg z-20 max-h-96 overflow-y-auto">
                            <template x-if="loading">
                                <p class="text-xs text-gray-400 px-4 py-3">Mencari...</p>
                            </template>
                            <template x-if="!loading && results && results.aset.length === 0 && results.healthcheck.length === 0">
                                <p class="text-xs text-gray-400 px-4 py-3">Gak ada hasil buat "<span x-text="q"></span>".</p>
                            </template>
                            <template x-if="!loading && results && results.aset.length > 0">
                                <div>
                                    <p class="px-4 pt-2.5 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wide">Aset</p>
                                    <template x-for="item in results.aset" :key="'aset-' + item.id">
                                        <a :href="item.url" class="block px-4 py-2 hover:bg-gray-50">
                                            <p class="text-sm font-semibold text-gray-800" x-text="item.title"></p>
                                            <p class="text-xs text-gray-400" x-text="item.subtitle"></p>
                                        </a>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!loading && results && results.healthcheck.length > 0">
                                <div>
                                    <p class="px-4 pt-2.5 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-wide border-t border-gray-100">Health Check</p>
                                    <template x-for="item in results.healthcheck" :key="'hc-' + item.id">
                                        <a :href="item.url" class="block px-4 py-2 hover:bg-gray-50">
                                            <p class="text-sm font-semibold text-gray-800" x-text="item.title"></p>
                                            <p class="text-xs text-gray-400" x-text="item.subtitle"></p>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                    @php
                        $notifInitial = [
                            'count' => auth()->user()->unreadNotifications->count(),
                            'pollUrl' => route('notifications.poll'),
                            'items' => auth()->user()->notifications->take(8)->map(fn ($n) => [
                                'id' => $n->id,
                                'message' => $n->data['message'] ?? '',
                                'read' => (bool) $n->read_at,
                                'created_at' => $n->created_at->diffForHumans(),
                            ])->values(),
                        ];
                    @endphp
                    <div x-data="notifBell(@js($notifInitial))">
                        <x-dropdown align="right" width="w-80">
                            <x-slot name="trigger">
                                <button class="relative w-9 h-9 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-500 hover:bg-gray-100">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"></path></svg>
                                    <span x-show="count > 0" x-cloak class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 border border-white"></span>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 pt-1 pb-2 flex items-center justify-between gap-2">
                                    <p class="text-sm font-bold text-gray-800">Notifikasi</p>
                                    <form x-show="count > 0" x-cloak method="POST" action="{{ route('notifications.readAll') }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-cakrawala hover:underline">Tandai semua dibaca</button>
                                    </form>
                                </div>
                                <div class="px-4 pb-2 flex items-center gap-1.5">
                                    <button type="button" @click="filter = 'semua'" class="text-[11px] font-semibold px-2 py-0.5 rounded-full" :class="filter === 'semua' ? 'bg-cakrawala text-white' : 'bg-gray-100 text-gray-500'">Semua</button>
                                    <button type="button" @click="filter = 'belum'" class="text-[11px] font-semibold px-2 py-0.5 rounded-full" :class="filter === 'belum' ? 'bg-cakrawala text-white' : 'bg-gray-100 text-gray-500'">Belum dibaca</button>
                                </div>
                                <div class="border-t border-gray-100"></div>
                                <div class="max-h-80 overflow-y-auto">
                                    <template x-if="itemsTertampil.length === 0">
                                        <p class="px-4 py-6 text-xs text-gray-400 text-center" x-text="filter === 'belum' ? 'Semua notifikasi sudah dibaca.' : 'Belum ada notifikasi.'"></p>
                                    </template>
                                    <template x-for="notif in itemsTertampil" :key="notif.id">
                                        <form method="POST" :action="'/notifications/' + notif.id + '/read'">
                                            <input type="hidden" name="_token" :value="csrfToken">
                                            <button type="submit" class="w-full text-left px-4 py-2.5 hover:bg-gray-50" :class="notif.read ? '' : 'bg-cakrawala/5'">
                                                <p class="text-xs text-gray-700 leading-snug" x-text="notif.message"></p>
                                                <p class="text-[11px] text-gray-400 mt-1" x-text="notif.created_at"></p>
                                            </button>
                                        </form>
                                    </template>
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 border border-gray-200 bg-gray-50 rounded-lg pl-1.5 pr-2.5 py-1.5">
                                <div class="w-7 h-7 rounded-full bg-cakrawala text-white flex items-center justify-center text-xs font-extrabold">
                                    {{ collect(explode(' ', auth()->user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                </div>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-gray-400"><path d="M6 9l6 6 6-6"></path></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 pt-1 pb-2">
                                <p class="text-sm font-bold text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Petugas Cabang' }}</p>
                            </div>
                            <div class="border-t border-gray-100"></div>
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                    </div>
                </header>

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
