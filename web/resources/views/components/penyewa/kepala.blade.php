@props(['penyewa', 'lease' => null, 'status' => null])

{{-- Kepala halaman penyewa: ikon rumah unitnya sendiri (warna mengikuti status tagihan), nama, kode, dan masa sewa. --}}
<header class="border-b border-ink/10">
    <div class="flex items-end gap-3.5 px-4 pt-4">
        <x-rumah :warna="$status ? \App\Support\WarnaStatus::ikon($status) : 'text-roof'" class="mb-2.5 h-14 w-16 shrink-0 drop-shadow-sm" />
        <div class="min-w-0 flex-1 pb-3">
            <p class="text-[13px] text-muted">{{ $lease ? 'Unit '.$lease->unit->kode_unit : 'Belum ada unit' }}, <span class="font-bold text-roof">{{ $penyewa->kode_penyewa }}</span></p>
            <p class="truncate text-[17px] font-extrabold tracking-tight">{{ $penyewa->name }}</p>
            @if ($lease)
                <p class="mt-0.5 text-xs text-muted">Menyewa sejak {{ \App\Support\Format::tanggal($lease->tgl_mulai) }}</p>
            @endif
        </div>
        <div class="mb-3 flex shrink-0 flex-col items-end gap-0.5">
            <a href="{{ route('penyewa.profil') }}" class="rounded-lg px-2.5 py-1 text-sm font-semibold text-dicek hover:bg-dicek-bg">Profil</a>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Apakah kamu yakin ingin keluar dari akun?')">
                @csrf
                <button type="submit" class="flex items-center gap-1 rounded-lg px-2.5 py-1 text-sm font-bold text-telat hover:bg-telat-bg">
                    <svg viewBox="0 0 24 24" class="size-4" aria-hidden="true"><path d="M14 5V4H5v16h9v-1M10 12h10M17 9l3 3-3 3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Keluar
                </button>
            </form>
        </div>
    </div>
</header>
