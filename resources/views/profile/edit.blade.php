<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-lg text-gray-800">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="p-7 space-y-5 max-w-3xl">
        <x-card padding="p-6 sm:p-8">
            <h3 class="font-extrabold text-sm text-gray-800 mb-4">Info Akun</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-gray-500 mb-0.5">PN</dt>
                    <dd class="font-mono text-gray-800">{{ $user->pn ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 mb-0.5">Role</dt>
                    <dd class="text-gray-800">{{ $user->role === 'admin' ? 'Administrator' : 'Petugas Cabang' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 mb-0.5">Uker</dt>
                    <dd class="text-gray-800">{{ $user->ukerRelasi?->nama ?? '-' }}</dd>
                </div>
            </dl>
            <p class="text-xs text-gray-400 mt-4">PN, role, dan uker cuma bisa diubah lewat admin -- hubungi admin kalau ada yang gak sesuai.</p>
        </x-card>

        <x-card padding="p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </x-card>

        <x-card padding="p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </x-card>

        <x-card padding="p-6 sm:p-8">
            <h3 class="font-extrabold text-sm text-gray-800 mb-1">Login History Saya</h3>
            <p class="text-xs text-gray-500 mb-4">15 percobaan login terakhir ke akun ini -- kalau ada yang bukan kamu, segera ganti password & hubungi admin.</p>

            @if ($loginLogsSaya->isEmpty())
                <p class="text-sm text-gray-400">Belum ada riwayat login tercatat.</p>
            @else
                <div class="space-y-2">
                    @foreach ($loginLogsSaya as $log)
                        @php
                            $warnaStatus = $log->status === \App\Models\LoginLog::STATUS_BERHASIL ? 'green' : 'red';
                        @endphp
                        <div class="flex items-center justify-between gap-3 text-sm border-b border-gray-100 pb-2 last:border-0">
                            <div class="min-w-0">
                                <p class="text-gray-700">{{ $log->created_at->format('d M Y H:i:s') }}</p>
                                <p class="text-xs text-gray-400 font-mono truncate">{{ $log->ip_address }}</p>
                            </div>
                            <x-badge :color="$warnaStatus" class="flex-none">{{ \App\Models\LoginLog::LABEL_STATUS[$log->status] ?? $log->status }}</x-badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
