@php use App\Support\Format; @endphp

<x-layouts.penyewa title="Riwayat bayar">
    <div class="p-4">
        <a href="{{ route('penyewa.beranda') }}" class="text-sm text-muted">Kembali</a>
        <h1 class="mt-3 text-[22px] font-extrabold tracking-tight">Riwayat bayar</h1>
        <p class="mt-1 text-sm text-muted">Tagihan yang sudah lunas.</p>

        @if ($lunas->isEmpty())
            <div class="mt-4 rounded-xl border border-line bg-paper p-4">
                <p class="font-semibold">Belum ada tagihan yang lunas</p>
                <p class="mt-1 text-sm text-muted">Setelah pengelola menyetujui bukti transfer, tagihannya muncul di sini.</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-line rounded-xl border border-line bg-paper">
                @foreach ($lunas as $invoice)
                    <li class="flex items-center justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ Format::bulan($invoice->periode) }}</p>
                            <p class="text-xs text-muted">
                                {{ Format::rupiah($invoice->total) }}, unit {{ $invoice->lease->unit->kode_unit }}
                                @if ($invoice->paymentTerakhir?->reviewed_at)
                                    , lunas {{ Format::tanggalPendek($invoice->paymentTerakhir->reviewed_at) }}
                                @endif
                            </p>
                        </div>
                        <svg viewBox="0 0 24 24" class="size-[22px] shrink-0" role="img" aria-label="Lunas"><circle cx="12" cy="12" r="11" fill="#e2efe7"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#2e6a4c" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.penyewa>
