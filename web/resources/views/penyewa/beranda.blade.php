<x-layouts.penyewa title="Beranda">
    <header class="border-b border-line bg-paper">
        <div class="atap h-3.5"></div>
        <div class="flex items-end gap-3.5 px-4 pt-4">
            <div class="grid h-[70px] w-11 shrink-0 grid-rows-[1fr_1.3fr] gap-[5px] rounded-t-[3px] bg-roof p-1.5" aria-hidden="true">
                <i class="rounded-[2px] bg-white/15"></i>
                <i class="rounded-[2px] bg-white/15"></i>
            </div>
            <div class="pb-3">
                <p class="text-[13px] text-muted">Halo,</p>
                <p class="text-[17px] font-extrabold tracking-tight">{{ auth()->user()->name }}</p>
            </div>
        </div>
        <div class="teras h-2"></div>
    </header>

    @if (in_array(auth()->user()->statusKtp(), ['belum_ada', 'ditolak'], true))
        <a href="{{ route('penyewa.ktp') }}" class="m-3 block rounded-xl border border-dekat/40 bg-dekat-bg p-4">
            <p class="font-bold text-dekat">Lengkapi data KTP</p>
            <p class="mt-1 text-sm text-ink">
                {{ auth()->user()->statusKtp() === 'ditolak' ? 'Pengelola meminta KTP diupload ulang.' : 'Upload foto KTP sebagai data identitas yang sah.' }}
            </p>
        </a>
    @endif

    <div class="m-3 rounded-xl border border-line bg-paper p-4">
        <p class="font-semibold">Belum ada tagihan</p>
        <p class="mt-1 text-sm text-muted">Tagihan sewa akan muncul di sini setelah pengelola mendaftarkan unit kamu.</p>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mx-3 mt-6" onsubmit="return confirm('Apakah kamu yakin ingin keluar dari akun?')">
        @csrf
        <button type="submit" class="w-full rounded-xl border border-telat px-4 py-3 text-sm font-bold text-telat">Keluar</button>
    </form>
</x-layouts.penyewa>
