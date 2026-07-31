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
        <div class="min-h-screen flex bg-gray-50">

            {{-- Sidebar --}}
            <aside class="w-64 flex-none bg-gradient-to-b from-nusantara to-slate-900 text-white flex flex-col px-4 py-6 gap-6 sticky top-0 h-screen">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-2">
                    <span class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center flex-none">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span class="text-base font-extrabold tracking-tight">AsetSehat</span>
                </a>

                <nav class="flex flex-col gap-1">
                    @php
                        $navMain = [
                            ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
                            ['route' => 'aset.index', 'pattern' => 'aset.*', 'label' => 'Data Aset', 'icon' => 'M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8'],
                            ['route' => 'healthcheck.index', 'pattern' => 'healthcheck.*', 'label' => 'Health Check', 'icon' => 'M9 12l2 2 4-4M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
                        ];
                    @endphp
                    @foreach ($navMain as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs($item['pattern']) ? 'bg-white/20' : 'hover:bg-white/10' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] flex-none opacity-95"><path d="{{ $item['icon'] }}"></path></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                @if (auth()->user()->role === 'admin')
                    <div class="flex flex-col gap-1">
                        <p class="px-3 mb-1 text-[10.5px] font-bold uppercase tracking-wider text-white/50">Administrasi</p>
                        @php
                            $navAdmin = [
                                ['route' => 'rekap.cabang', 'pattern' => 'rekap.*', 'label' => 'Rekap Cabang', 'icon' => 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
                                ['route' => 'users.index', 'pattern' => 'users.*', 'label' => 'Kelola User', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75'],
                                ['route' => 'ukers.index', 'pattern' => 'ukers.*', 'label' => 'Kelola Uker', 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1M9 21v-4h6v4'],
                                ['route' => 'aset.editRequests.index', 'pattern' => 'aset.editRequests.*', 'label' => 'Permintaan Edit Aset', 'icon' => 'M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
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
                <header class="h-16 flex-none flex items-center justify-between gap-4 px-7 border-b border-gray-200 bg-white sticky top-0 z-10">
                    <div class="min-w-0">
                        @isset($header)
                            {{ $header }}
                        @endisset
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
                </header>

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
