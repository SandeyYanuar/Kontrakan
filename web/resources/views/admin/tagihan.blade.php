{{-- Sementara (Langkah 6). Diganti tampilan deretan pintu dan tabel lengkap di Langkah 7. --}}
<x-layouts.admin title="Tagihan" aktif="tagihan">
    <x-judul :keterangan="'Periode bayar tanggal '.\App\Models\Pengaturan::keteranganPeriodeBayar().'.'">Tagihan {{ \App\Support\Format::bulan($periode) }}</x-judul>

    @if ($tagihan->isEmpty())
        <div class="rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
            <p class="font-semibold">Belum ada tagihan bulan ini</p>
            <p class="mt-1 text-sm text-muted">Tagihan dibuat otomatis 14 hari sebelum jatuh tempo untuk setiap penyewa aktif.</p>
        </div>
    @else
        <ul class="divide-y divide-line rounded-2xl border border-line bg-paper">
            @foreach ($tagihan as $invoice)
                <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                    <span><b>{{ $invoice->lease->unit->kode_unit }}</b> {{ $invoice->lease->penyewa->name }}</span>
                    <span>{{ \App\Support\Format::rupiah($invoice->total) }} &middot; {{ $invoice->label() }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.admin>
