@props(['title' => null, 'aktif' => null])

@php
    /**
     * Menu admin di strip atap. Item bertanda super_admin nanti hanya tampil untuk super admin (Langkah 2).
     *
     * @var array<int, array{kunci: string, label: string, url: string, super_admin: bool}> $menu
     */
    $menu = [
        ['kunci' => 'tagihan', 'label' => 'Tagihan', 'url' => '#', 'super_admin' => false],
        ['kunci' => 'penyewa', 'label' => 'Penyewa', 'url' => '#', 'super_admin' => false],
        ['kunci' => 'unit', 'label' => 'Unit', 'url' => '#', 'super_admin' => false],
        ['kunci' => 'riwayat', 'label' => 'Riwayat aktivitas', 'url' => '#', 'super_admin' => false],
        ['kunci' => 'admin', 'label' => 'Admin', 'url' => '#', 'super_admin' => true],
        ['kunci' => 'pengaturan', 'label' => 'Pengaturan', 'url' => '#', 'super_admin' => true],
    ];
@endphp

<x-layouts.app :title="$title">
    <nav class="atap sticky top-0 z-30 flex items-center gap-1 overflow-x-auto px-5" aria-label="Menu admin">
        <span class="mr-4 whitespace-nowrap py-3.5 text-base font-extrabold text-white">{{ config('app.name') }}</span>

        @foreach ($menu as $item)
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
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-8 sm:py-8">
        {{ $slot }}
    </main>
</x-layouts.app>
