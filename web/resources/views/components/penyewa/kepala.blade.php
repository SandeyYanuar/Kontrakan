@props(['penyewa', 'lease' => null, 'status' => null])

{{-- Kepala halaman penyewa: atap, ikon rumah unitnya sendiri (warna mengikuti status tagihan), nama, kode, dan masa sewa. --}}
<header class="border-b border-line bg-paper">
    <div class="atap h-3.5"></div>
    <div class="flex items-end gap-3.5 px-4 pt-4">
        <x-rumah :warna="$status ? \App\Support\WarnaStatus::ikon($status) : 'text-roof'" class="mb-2.5 h-14 w-16 shrink-0 drop-shadow-sm" />
        <div class="min-w-0 flex-1 pb-3">
            <p class="text-[13px] text-muted">{{ $lease ? 'Unit '.$lease->unit->kode_unit : 'Belum ada unit' }}</p>
            <p class="truncate text-[17px] font-extrabold tracking-tight">
                {{ $penyewa->name }}
                <span class="ml-1 rounded-[5px] bg-[#e4e8ed] px-1.5 py-px align-[1px] text-[11px] font-bold tracking-wide text-roof">{{ $penyewa->kode_penyewa }}</span>
            </p>
            @if ($lease)
                <p class="mt-0.5 text-xs text-muted">Menyewa sejak {{ \App\Support\Format::tanggal($lease->tgl_mulai) }}</p>
            @endif
        </div>
        <a href="{{ route('penyewa.profil') }}" class="mb-3 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-dicek hover:bg-dicek-bg">Profil</a>
    </div>
    <div class="teras h-2"></div>
</header>
