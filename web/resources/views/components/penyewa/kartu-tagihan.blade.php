@props(['invoice'])

@php
    use App\Models\Invoice;
    use App\Support\Format;
    use App\Support\PeriodeBayar;

    $label = $invoice->label();
    $bukti = $invoice->paymentTerakhir;
    $sisaHari = (int) now()->startOfDay()->diffInDays($invoice->jatuh_tempo);
    $warna = match ($label) {
        Invoice::LABEL_DEKAT => 'bg-dekat text-white',
        Invoice::LABEL_TELAT, Invoice::LABEL_DITOLAK => 'bg-telat text-white',
        Invoice::LABEL_BELUM_PERIODE => 'bg-roof text-white',
        Invoice::LABEL_MENUNGGU => 'border-2 border-dicek bg-paper text-ink',
        default => 'bg-lunas text-white',
    };
    $warnaTombol = match ($label) {
        Invoice::LABEL_DEKAT => 'text-dekat',
        Invoice::LABEL_TELAT, Invoice::LABEL_DITOLAK => 'text-telat',
        default => 'text-roof',
    };
@endphp

<article class="{{ $warna }} m-3 rounded-[14px] p-4">
    @if ($label === Invoice::LABEL_MENUNGGU)
        <span class="mb-2 inline-flex rounded-full bg-dicek-bg px-2.5 py-1 text-xs font-bold text-dicek">Menunggu dicek pengelola</span>
    @endif

    <p @class(['text-[13px]', 'opacity-90' => $label !== Invoice::LABEL_MENUNGGU, 'text-muted' => $label === Invoice::LABEL_MENUNGGU])>Tagihan {{ Format::bulan($invoice->periode) }}</p>
    <p class="mt-0.5 mb-1 text-[30px] font-extrabold tracking-tight">{{ Format::rupiah($invoice->total) }}</p>

    <p @class(['text-[13px]', 'opacity-95' => $label !== Invoice::LABEL_MENUNGGU, 'text-muted' => $label === Invoice::LABEL_MENUNGGU])>
        @switch($label)
            @case(Invoice::LABEL_DEKAT)
                Jatuh tempo {{ Format::tanggalBulan($invoice->jatuh_tempo) }}, {{ $sisaHari === 0 ? 'hari ini' : $sisaHari.' hari lagi' }}
                @break
            @case(Invoice::LABEL_TELAT)
                Telat {{ $invoice->hariTelat() }} hari. Jatuh temponya {{ Format::tanggalBulan($invoice->jatuh_tempo) }}.
                @break
            @case(Invoice::LABEL_BELUM_PERIODE)
                Bisa dibayar mulai {{ Format::tanggalBulan(PeriodeBayar::mulaiBayar($invoice->periode)) }}. Jatuh tempo {{ Format::tanggalBulan($invoice->jatuh_tempo) }}.
                @break
            @case(Invoice::LABEL_DITOLAK)
                <b class="block text-[15px]">Bukti transfer ditolak</b>
                Alasan: {{ $bukti?->alasan }}
                @break
            @case(Invoice::LABEL_MENUNGGU)
                Bukti dikirim {{ $bukti ? Format::tanggalPendek($bukti->created_at).', '.$bukti->created_at->format('H.i') : '' }}.
                Status berubah jadi lunas setelah pengelola mengecek rekening.
                @break
        @endswitch
    </p>

    @if ($invoice->bisaDibayar())
        <a href="{{ route('penyewa.bayar', $invoice) }}" class="{{ $warnaTombol }} mt-3.5 block w-full rounded-[10px] bg-white px-4 py-3 text-center text-sm font-bold">
            {{ $label === Invoice::LABEL_DITOLAK ? 'Upload ulang' : 'Upload bukti transfer' }}
        </a>
    @endif
</article>
