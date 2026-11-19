@props(['title' => null, 'aktif' => null])

@php
    /**
     * Menu admin di strip atap. Item super_admin hanya tampil untuk super admin.
     *
     * @var array<int, array{kunci: string, label: string, url: string, super_admin: bool}> $menu
     */
    $menu = [
        ['kunci' => 'tagihan', 'label' => 'Tagihan', 'url' => route('admin.tagihan'), 'super_admin' => false],
        ['kunci' => 'penyewa', 'label' => 'Penyewa', 'url' => '#', 'super_admin' => false],
        ['kunci' => 'unit', 'label' => 'Unit', 'url' => '#', 'super_admin' => false],
        ['kunci' => 'riwayat', 'label' => 'Riwayat aktivitas', 'url' => '#', 'super_admin' => false],
        ['kunci' => 'admin', 'label' => 'Admin', 'url' => '#', 'super_admin' => true],
        ['kunci' => 'pengaturan', 'label' => 'Pengaturan', 'url' => route('admin.pengaturan'), 'super_admin' => true],
    ];
@endphp

<x-layouts.app :title="$title">
    <nav class="atap sticky top-0 z-30 flex items-center gap-1 overflow-x-auto px-5" aria-label="Menu admin">
        <span class="mr-4 whitespace-nowrap py-3.5 text-base font-extrabold text-white">{{ config('app.name') }}</span>

        @foreach ($menu as $item)
            @continue($item['super_admin'] && auth()->user()->cannot('super-admin'))
            <a
                href="{{ $item['url'] }}"
                @class([
                    'whitespace-nowrap border-b-3 px-3 pt-4 pb-3 text-sm font-semibold focus-visible:outline-amber-300',
                    'border-amber-300 text-white' => $aktif === $item['kunci'],
                    'border-transparent text-slate-300 hover:text-white' => $aktif !== $item['kunci'],
                ])
                @if ($aktif === $item['kunci']) aria-current="page" @endif
            >{{ $item['label'] }}</a>
        @endforeach

        <div class="ml-auto flex items-center gap-3 pl-4">
            <span class="whitespace-nowrap text-sm text-slate-300">
                {{ auth()->user()->name }}
                <span class="text-slate-400">({{ auth()->user()->role->label() }})</span>
            </span>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Keluar dari akun?')">
                @csrf
                <button type="submit" class="whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-semibold text-white hover:bg-white/10 focus-visible:outline-amber-300">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-8 sm:py-8">
        {{ $slot }}
    </main>
</x-layouts.app>
