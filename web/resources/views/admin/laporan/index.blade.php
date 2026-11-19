@php
    use App\Support\Format;
    $kelompok = $laporan->groupBy(fn ($l) => $l->aktif() ? 'aktif' : 'beres');
@endphp

<x-layouts.admin title="Laporan kerusakan" aktif="laporan">
    <x-judul keterangan="Laporan dari penyewa. Jadwalkan kunjungan, tandai sedang dikerjakan, lalu selesai. Penyewa mengonfirmasi dari HP-nya kalau sudah beres.">Laporan kerusakan</x-judul>

    @if ($laporan->isEmpty())
        <div class="rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
            <p class="font-semibold">Belum ada laporan kerusakan</p>
            <p class="mt-1 text-sm text-muted">Penyewa melapor dari tombol Lapor kerusakan di berandanya.</p>
        </div>
    @endif

    @foreach (['aktif' => 'Perlu ditangani', 'beres' => 'Sudah beres'] as $kunci => $judul)
        @continue(! $kelompok->has($kunci))
        <h2 class="mt-6 mb-2.5 text-[13px] font-bold text-muted first:mt-0">{{ $judul }} ({{ $kelompok[$kunci]->count() }})</h2>
        <div class="grid gap-2.5 md:grid-cols-2">
            @foreach ($kelompok[$kunci] as $item)
                <a href="{{ route('admin.laporan.show', $item) }}" class="block rounded-xl border border-line bg-paper p-4 hover:border-dicek">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="grid size-[38px] shrink-0 place-items-center rounded-lg bg-roof text-[13px] font-extrabold text-white">{{ $item->lease->unit->kode_unit }}</span>
                            <div>
                                <p class="font-bold">{{ $item->labelKategori() }}</p>
                                <p class="text-xs text-muted">{{ $item->lease->penyewa->name }} ({{ $item->lease->penyewa->kode_penyewa }}), {{ Format::tanggalPendek($item->created_at) }}</p>
                            </div>
                        </div>
                        <span @class([
                            'shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-dekat-bg text-dekat' => $item->status->value === 'diterima',
                            'bg-dicek-bg text-dicek' => in_array($item->status->value, ['dijadwalkan', 'dikerjakan'], true),
                            'bg-lunas-bg text-lunas' => $item->status->value === 'selesai',
                        ])>{{ $item->status === \App\Enums\StatusLaporan::Selesai && ! $item->dikonfirmasi_at ? 'Menunggu konfirmasi' : $item->status->label() }}</span>
                    </div>
                    <p class="mt-2 line-clamp-2 text-sm text-muted">{{ $item->deskripsi }}</p>
                </a>
            @endforeach
        </div>
    @endforeach
</x-layouts.admin>
