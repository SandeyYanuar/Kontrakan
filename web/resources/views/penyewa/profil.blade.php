<x-layouts.penyewa title="Profil">
    <div class="p-4">
        <a href="{{ route('penyewa.beranda') }}" class="text-sm text-muted">Kembali</a>
        <h1 class="mt-3 text-[22px] font-extrabold tracking-tight">Profil</h1>
        <p class="mt-1 text-sm text-muted">{{ $penyewa->name }}, {{ $penyewa->kode_penyewa }}, {{ \App\Support\NomorHp::tampil($penyewa->phone) }}</p>

        @session('status')
            <p class="mt-4 rounded-[10px] border border-lunas/30 bg-lunas-bg px-4 py-3 text-sm font-semibold text-lunas" role="status">{{ $value }}</p>
        @endsession

        <nav class="mt-4 divide-y divide-line rounded-xl border border-line bg-paper">
            <a href="{{ route('penyewa.ktp') }}" class="flex items-center justify-between px-4 py-3.5">
                <span class="font-semibold">Data KTP</span>
                <span class="text-xs text-muted">{{ $penyewa->identitas?->labelStatus() ?? 'Belum ada' }}</span>
            </a>
            <a href="{{ route('akun.password') }}" class="block px-4 py-3.5 font-semibold">Ganti password</a>
        </nav>

        <form method="POST" action="{{ route('penyewa.profil.darurat') }}" class="mt-4 space-y-3 rounded-xl border border-line bg-paper p-4" novalidate>
            @csrf
            @method('PUT')
            <x-field label="Nomor HP darurat" name="phone_darurat" type="tel" inputmode="tel"
                :value="$penyewa->phone_darurat ? \App\Support\NomorHp::tampil($penyewa->phone_darurat) : null"
                hint="Keluarga atau kerabat yang bisa dihubungi kalau ada keadaan darurat." opsional />
            <button type="submit" class="rounded-[10px] border border-line bg-soft px-4 py-2.5 text-sm font-semibold">Simpan nomor darurat</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-8" onsubmit="return confirm('Apakah kamu yakin ingin keluar dari akun?')">
            @csrf
            <button type="submit" class="w-full rounded-xl border border-telat bg-paper px-4 py-3 text-sm font-bold text-telat">Keluar</button>
        </form>
    </div>
</x-layouts.penyewa>
