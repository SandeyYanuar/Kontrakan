@props(['title' => null, 'aktif' => null])

@php
    /**
     * Menu admin. Item super_admin hanya tampil untuk super admin; item dengan "hp" tampil di bilah bawah HP.
     *
     * @var array<string, list<array{kunci: string, label: string, url: string, super_admin: bool, hp: bool, ikon: string}>> $grup
     */
    $ikon = [
        'tagihan' => '<path d="M4 5h16v14H4zM4 10h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'penyewa' => '<circle cx="12" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M5 20c1-4 4-6 7-6s6 2 7 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'laporan' => '<path d="M14.5 5.5a4 4 0 0 0-5 5L4 16l4 4 5.5-5.5a4 4 0 0 0 5-5l-2.5 2.5-2.5-.5-.5-2.5 2.5-2.5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
        'pengumuman' => '<path d="M4 10v4h3l6 4V6L7 10H4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M17 9a4 4 0 0 1 0 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'unit' => '<path d="M4 11 12 4l8 7v9H4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M10 20v-5h4v5" fill="none" stroke="currentColor" stroke-width="2"/>',
        'riwayat' => '<circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'admin' => '<circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 19c.8-3 3-5 6-5s5.2 2 6 5M16 5a3 3 0 0 1 0 6M18 14c1.6.6 2.6 2.2 3 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'pengaturan' => '<circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'lainnya' => '<circle cx="5" cy="12" r="1.8" fill="currentColor"/><circle cx="12" cy="12" r="1.8" fill="currentColor"/><circle cx="19" cy="12" r="1.8" fill="currentColor"/>',
    ];

    $grup = [
        'Harian' => [
            ['kunci' => 'tagihan', 'label' => 'Tagihan', 'url' => route('admin.tagihan'), 'super_admin' => false, 'hp' => true],
            ['kunci' => 'penyewa', 'label' => 'Penyewa', 'url' => route('admin.penyewa.index'), 'super_admin' => false, 'hp' => true],
            ['kunci' => 'laporan', 'label' => 'Kerusakan', 'url' => route('admin.laporan.index'), 'super_admin' => false, 'hp' => true],
            ['kunci' => 'pengumuman', 'label' => 'Pengumuman', 'url' => route('admin.pengumuman.index'), 'super_admin' => false, 'hp' => false],
        ],
        'Kelola' => [
            ['kunci' => 'unit', 'label' => 'Unit', 'url' => route('admin.unit.index'), 'super_admin' => false, 'hp' => false],
            ['kunci' => 'riwayat', 'label' => 'Riwayat', 'url' => route('admin.riwayat'), 'super_admin' => false, 'hp' => false],
            ['kunci' => 'admin', 'label' => 'Admin', 'url' => route('admin.admin.index'), 'super_admin' => true, 'hp' => false],
            ['kunci' => 'pengaturan', 'label' => 'Pengaturan', 'url' => route('admin.pengaturan'), 'super_admin' => true, 'hp' => false],
        ],
    ];

    $bolehLihat = fn (array $item): bool => ! $item['super_admin'] || auth()->user()->can('super-admin');
    $semuaMenu = collect($grup)->flatten(1)->filter($bolehLihat);
    $lainnyaAktif = $semuaMenu->first(fn ($item) => ! $item['hp'] && $item['kunci'] === $aktif) !== null;

    $jumlahMenu ??= ['tagihan' => 0, 'laporan' => 0];
    $angka = [
        'tagihan' => [$jumlahMenu['tagihan'], $jumlahMenu['tagihan'].' tagihan perlu ditindak'],
        'laporan' => [$jumlahMenu['laporan'], $jumlahMenu['laporan'].' laporan belum selesai'],
    ];

    $latar = \App\Models\Pengaturan::ambil('latar_dashboard')
        ? route('admin.latar', ['v' => substr(md5(\App\Models\Pengaturan::ambil('latar_dashboard')), 0, 8)])
        : asset('img/latar-rumah.jpg');
    $nama = \App\Models\Pengaturan::ambil('nama_kontrakan');
@endphp

<x-layouts.app :title="$title" class="bg-[#cfc6ba]">
    {{-- Latar foto rumah, sedikit diredupkan supaya kaca dan tulisan tetap jelas. --}}
    <div class="fixed inset-0 -z-10 bg-cover bg-center saturate-[.85]" style="background-image: url('{{ $latar }}')" aria-hidden="true"></div>
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-[#f3ede6]/35 via-transparent to-[#18222f]/25" aria-hidden="true"></div>

    {{-- Laptop: bilah samping kaca. --}}
    <aside class="kaca fixed inset-y-3 left-3 z-30 hidden w-60 flex-col rounded-[20px] px-3 py-4 lg:flex" aria-label="Menu admin">
        <a href="{{ route('admin.tagihan') }}" class="mb-2 flex items-center gap-2.5 px-2 pb-3">
            <x-rumah warna="text-lunas" class="size-8 shrink-0" />
            <span class="text-[15px] leading-tight font-extrabold">{{ $nama }}</span>
        </a>

        <nav class="flex-1 overflow-y-auto">
            @foreach ($grup as $judulGrup => $daftar)
                <p class="px-2.5 pt-3 pb-1.5 text-[11px] font-bold text-muted">{{ $judulGrup }}</p>
                @foreach ($daftar as $item)
                    @continue(! $bolehLihat($item))
                    <a href="{{ $item['url'] }}" @if ($aktif === $item['kunci']) aria-current="page" @endif
                        @class([
                            'mb-0.5 flex items-center justify-between gap-2.5 rounded-[10px] px-2.5 py-2 text-sm font-semibold',
                            'bg-white/80 text-ink shadow-sm' => $aktif === $item['kunci'],
                            'text-ink/80 hover:bg-white/45' => $aktif !== $item['kunci'],
                        ])>
                        <span class="flex items-center gap-2.5">
                            <svg viewBox="0 0 24 24" class="size-[18px] shrink-0" aria-hidden="true">{!! $ikon[$item['kunci']] !!}</svg>
                            {{ $item['label'] }}
                        </span>
                        @if (($angka[$item['kunci']][0] ?? 0) > 0)
                            <span class="rounded-full bg-telat px-[7px] py-px text-[11px] font-extrabold text-white" aria-label="{{ $angka[$item['kunci']][1] }}">{{ $angka[$item['kunci']][0] }}</span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="mt-2 flex items-center gap-2.5 border-t border-ink/10 px-2 pt-3">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-roof text-[13px] font-extrabold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            <a href="{{ route('akun.password') }}" class="min-w-0 flex-1" title="Ganti password">
                <span class="block truncate text-[13px] font-bold">{{ auth()->user()->name }}</span>
                <span class="block text-[11px] text-muted">{{ auth()->user()->role->label() }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Keluar dari akun?')">
                @csrf
                <button type="submit" class="rounded-lg px-2 py-1.5 text-xs font-bold text-telat hover:bg-white/60">Keluar</button>
            </form>
        </div>
    </aside>

    {{-- HP: bilah atas kaca. --}}
    <header class="kaca sticky top-2 z-30 mx-2 mt-2 flex items-center justify-between gap-3 rounded-2xl px-3 py-2.5 lg:hidden">
        <a href="{{ route('admin.tagihan') }}" class="flex min-w-0 items-center gap-2">
            <x-rumah warna="text-lunas" class="size-7 shrink-0" />
            <span class="truncate text-[15px] font-extrabold">{{ $nama }}</span>
        </a>
        <a href="{{ route('akun.password') }}" class="grid size-8 shrink-0 place-items-center rounded-full bg-roof text-[13px] font-extrabold text-white" title="{{ auth()->user()->name }}, ganti password">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
    </header>

    <main class="px-2 pt-3 pb-28 lg:py-3 lg:pr-3 lg:pl-[16.5rem]">
        <div class="kaca panel-kaca mx-auto min-h-[calc(100vh-1.5rem)] max-w-6xl rounded-[20px] px-4 py-6 sm:px-8 sm:py-8">
            @session('status')
                <p class="mb-5 rounded-[10px] border border-lunas/30 bg-lunas-bg px-4 py-3 text-sm font-semibold text-lunas" role="status">{{ $value }}</p>
            @endsession

            {{ $slot }}
        </div>
    </main>

    {{-- HP: bilah bawah kaca berisi menu utama, sisanya di "Lainnya". --}}
    <nav class="kaca fixed inset-x-2 bottom-2 z-30 flex justify-around rounded-2xl px-1.5 py-1.5 lg:hidden" aria-label="Menu bawah">
        @foreach ($semuaMenu->where('hp', true) as $item)
            <a href="{{ $item['url'] }}" @if ($aktif === $item['kunci']) aria-current="page" @endif
                @class(['relative flex min-w-16 flex-col items-center gap-0.5 rounded-xl px-2 py-1.5 text-[11px] font-bold', 'bg-white/80 text-ink' => $aktif === $item['kunci'], 'text-ink/70' => $aktif !== $item['kunci']])>
                <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true">{!! $ikon[$item['kunci']] !!}</svg>
                {{ $item['label'] }}
                @if (($angka[$item['kunci']][0] ?? 0) > 0)
                    <span class="absolute top-0.5 right-2 rounded-full bg-telat px-1.5 text-[10px] font-extrabold text-white">{{ $angka[$item['kunci']][0] }}</span>
                @endif
            </a>
        @endforeach
        <button type="button" data-buka-lainnya
            @class(['flex min-w-16 flex-col items-center gap-0.5 rounded-xl px-2 py-1.5 text-[11px] font-bold', 'bg-white/80 text-ink' => $lainnyaAktif, 'text-ink/70' => ! $lainnyaAktif])>
            <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true">{!! $ikon['lainnya'] !!}</svg>
            Lainnya
        </button>
    </nav>

    <dialog id="menu-lainnya" aria-label="Menu lainnya" class="kaca mx-2 mt-auto mb-20 w-[calc(100%-1rem)] max-w-none rounded-2xl p-2 text-ink backdrop:bg-ink/30 lg:hidden">
        @foreach ($semuaMenu->where('hp', false) as $item)
            <a href="{{ $item['url'] }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-semibold', 'bg-white/80' => $aktif === $item['kunci']])>
                <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true">{!! $ikon[$item['kunci']] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach
        <div class="mt-1 flex items-center justify-between border-t border-ink/10 px-3 pt-2">
            <a href="{{ route('akun.password') }}" class="py-2 text-sm font-semibold">Ganti password</a>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Keluar dari akun?')">
                @csrf
                <button type="submit" class="py-2 text-sm font-bold text-telat">Keluar</button>
            </form>
        </div>
    </dialog>

    <script>
        (function () {
            var menu = document.getElementById('menu-lainnya');
            document.querySelector('[data-buka-lainnya]').addEventListener('click', function () { menu.showModal(); });
            menu.addEventListener('click', function (e) { if (e.target === menu) { menu.close(); } });
        })();
    </script>
</x-layouts.app>
