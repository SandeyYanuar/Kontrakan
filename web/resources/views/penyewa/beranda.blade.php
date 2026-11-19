@php use App\Support\Format; @endphp

<x-layouts.penyewa title="Beranda">
    <x-penyewa.kepala :penyewa="$penyewa" :lease="$lease" :status="$statusPintu" />

    @session('status')
        <p class="m-3 rounded-[10px] border border-lunas/30 bg-lunas-bg px-4 py-3 text-sm font-semibold text-lunas" role="status">{{ $value }}</p>
    @endsession

    @if ($lewatBatas)
        <div class="m-3 rounded-xl border-2 border-telat bg-telat-bg p-4" role="alert">
            <p class="font-extrabold text-telat">Tunggakan sudah melewati batas {{ \App\Support\AturanTunggakan::BATAS_BULAN }} bulan</p>
            <p class="mt-1 text-sm text-ink">Mohon segera lunasi, mulai dari tagihan yang paling lama. Kalau ada kendala, hubungi pengelola.</p>
        </div>
    @endif

    @if (in_array($penyewa->statusKtp(), ['belum_ada', 'ditolak'], true))
        <a href="{{ route('penyewa.ktp') }}" class="m-3 block rounded-xl border border-dekat/40 bg-dekat-bg p-4">
            <p class="font-bold text-dekat">Lengkapi data KTP</p>
            <p class="mt-1 text-sm text-ink">
                {{ $penyewa->statusKtp() === 'ditolak' ? 'Pengelola meminta KTP diupload ulang.' : 'Upload foto KTP sebagai data identitas yang sah.' }}
            </p>
        </a>
    @endif

    @forelse ($terbuka as $invoice)
        <x-penyewa.kartu-tagihan :invoice="$invoice"
            :penghalang="$terbuka->first(fn ($lain) => $lain->periode < $invoice->periode && $lain->status->belumLunas())" />
    @empty
        @if ($lunasTerakhir)
            <article class="m-3 rounded-[14px] bg-lunas p-4 text-white">
                <span class="mb-2 grid size-[34px] place-items-center rounded-full bg-white/20" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-[18px]"><path d="M6 12.5l4 4L18 8" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <p class="text-[13px] opacity-90">Tagihan {{ Format::bulan($lunasTerakhir->periode) }} lunas</p>
                <p class="mt-0.5 text-[30px] font-extrabold tracking-tight">Lunas</p>
                <p class="text-[13px] opacity-95">{{ Format::rupiah($lunasTerakhir->total) }}. Tagihan berikutnya muncul 14 hari sebelum jatuh tempo.</p>
            </article>
        @else
            <div class="m-3 rounded-xl border border-line bg-paper p-4">
                <p class="font-semibold">Belum ada tagihan</p>
                <p class="mt-1 text-sm text-muted">
                    {{ $lease ? 'Tagihan sewa muncul di sini 14 hari sebelum jatuh tempo.' : 'Pengelola belum mendaftarkan unit kamu.' }}
                </p>
            </div>
        @endif
    @endforelse

    @if ($adaYangHarusDibayar && $pengaturan['bank_nomor'])
        <section class="m-3 rounded-xl border border-line bg-paper p-3.5">
            <h2 class="mb-2.5 text-sm font-bold">Transfer ke</h2>
            <div class="flex items-center justify-between gap-2.5">
                <div>
                    <p id="nomor-rekening" class="text-[17px] font-extrabold tracking-wide">{{ $pengaturan['bank_nomor'] }}</p>
                    <p class="text-xs text-muted">{{ $pengaturan['bank_nama'] }}, a.n. {{ $pengaturan['bank_atas_nama'] }}</p>
                </div>
                <button type="button" data-salin="{{ preg_replace('/\D/', '', $pengaturan['bank_nomor']) }}" class="rounded-lg border border-line bg-soft px-3 py-2 text-xs font-semibold">Salin</button>
            </div>
            <p class="mt-2.5 rounded-lg bg-soft px-2.5 py-2 text-xs text-muted">Tulis <b class="text-ink">{{ $penyewa->kode_penyewa }}</b> di berita transfer.</p>
        </section>
    @endif

    <div class="m-3 grid grid-cols-2 gap-2.5">
        @if ($linkPengelola)
            <a href="{{ $linkPengelola }}" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-xl border border-line bg-paper p-3 text-[13px] font-semibold">
                <svg viewBox="0 0 24 24" class="size-[18px] shrink-0 fill-wa" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>
                Hubungi pengelola
            </a>
        @endif
        <a href="{{ route('penyewa.riwayat') }}" class="flex items-center gap-2 rounded-xl border border-line bg-paper p-3 text-[13px] font-semibold">
            <svg viewBox="0 0 24 24" class="size-[18px] shrink-0" aria-hidden="true"><path d="M5 4h14v16H5zM9 8h6M9 12h6M9 16h3" fill="none" stroke="#2c4f86" stroke-width="2" stroke-linecap="round"/></svg>
            Riwayat bayar
        </a>
    </div>

    <script>
        document.querySelectorAll('[data-salin]').forEach(function (tombol) {
            tombol.addEventListener('click', function () {
                navigator.clipboard.writeText(tombol.dataset.salin).then(function () {
                    tombol.textContent = 'Tersalin';
                    setTimeout(function () { tombol.textContent = 'Salin'; }, 2000);
                });
            });
        });
    </script>
</x-layouts.penyewa>
