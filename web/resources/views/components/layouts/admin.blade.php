@props(['title' => null, 'aktif' => null])

@php
    /**
     * Menu admin di strip atap. Item super_admin hanya tampil untuk super admin.
     *
     * @var array<int, array{kunci: string, label: string, url: string, super_admin: bool}> $menu
     */
    $menu = [
        ['kunci' => 'tagihan', 'label' => 'Tagihan', 'url' => route('admin.tagihan'), 'super_admin' => false],
        ['kunci' => 'penyewa', 'label' => 'Penyewa', 'url' => route('admin.penyewa.index'), 'super_admin' => false],
        ['kunci' => 'unit', 'label' => 'Unit', 'url' => route('admin.unit.index'), 'super_admin' => false],
        ['kunci' => 'laporan', 'label' => 'Laporan kerusakan', 'url' => route('admin.laporan.index'), 'super_admin' => false],
        ['kunci' => 'riwayat', 'label' => 'Riwayat aktivitas', 'url' => route('admin.riwayat'), 'super_admin' => false],
        ['kunci' => 'admin', 'label' => 'Admin', 'url' => route('admin.admin.index'), 'super_admin' => true],
        ['kunci' => 'pengaturan', 'label' => 'Pengaturan', 'url' => route('admin.pengaturan'), 'super_admin' => true],
    ];
@endphp

<x-layouts.app :title="$title">
    {{-- Menu di strip atap. HP: nama + akun di baris atas, menu di baris bawah. Laptop: satu baris. --}}
    <nav class="atap sticky top-0 z-30 grid grid-cols-[1fr_auto] items-center px-3 md:flex md:px-5" aria-label="Menu admin">
        <span class="col-start-1 row-start-1 truncate py-3 pl-2 text-base font-extrabold text-white md:mr-4 md:py-3.5 md:pl-0">{{ \App\Models\Pengaturan::ambil('nama_kontrakan') }}</span>

        <div class="col-span-2 row-start-2 -mx-3 flex overflow-x-auto border-t border-white/10 px-3 md:order-none md:mx-0 md:gap-1 md:border-0 md:px-0">
            @foreach ($menu as $item)
                @continue($item['super_admin'] && auth()->user()->cannot('super-admin'))
                <a
                    href="{{ $item['url'] }}"
                    @class([
                        'whitespace-nowrap border-b-3 px-3 pt-3 pb-2.5 text-sm font-semibold focus-visible:outline-amber-300 md:pt-4 md:pb-3',
                        'border-amber-300 text-white' => $aktif === $item['kunci'],
                        'border-transparent text-slate-300 hover:text-white' => $aktif !== $item['kunci'],
                    ])
                    @if ($aktif === $item['kunci']) aria-current="page" @endif
                >{{ $item['label'] }}</a>
            @endforeach
        </div>

        <div class="col-start-2 row-start-1 flex items-center gap-1 md:ml-auto md:pl-4">
            <a href="{{ route('akun.password') }}" class="whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white focus-visible:outline-amber-300" title="Ganti password">
                {{ auth()->user()->name }}
                <span class="hidden text-slate-400 sm:inline">({{ auth()->user()->role->label() }})</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Keluar dari akun?')">
                @csrf
                <button type="submit" class="whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-semibold text-white hover:bg-white/10 focus-visible:outline-amber-300">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-8 sm:py-8">
        @session('status')
            <p class="mb-5 rounded-[10px] border border-lunas/30 bg-lunas-bg px-4 py-3 text-sm font-semibold text-lunas" role="status">{{ $value }}</p>
        @endsession

        {{ $slot }}
    </main>
</x-layouts.app>
