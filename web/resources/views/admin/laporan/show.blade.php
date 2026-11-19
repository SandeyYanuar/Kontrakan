@php
    use App\Enums\StatusLaporan;
    use App\Support\Format;
    use App\Support\NomorHp;

    $penyewa = $laporan->lease->penyewa;
    $pesanWa = "Halo {$penyewa->name} ({$penyewa->kode_penyewa}), soal laporan {$laporan->labelKategori()} di unit {$laporan->lease->unit->kode_unit}: ";
    if ($laporan->jadwal_kunjungan) {
        $pesanWa .= 'teknisi dijadwalkan datang '.$laporan->jadwal_kunjungan->locale('id')->translatedFormat('l, j F').' pukul '.$laporan->jadwal_kunjungan->format('H.i').'. ';
    }
@endphp

<x-layouts.admin :title="'Laporan '.$laporan->labelKategori()" aktif="laporan">
    <x-judul :keterangan="$penyewa->name.' ('.$penyewa->kode_penyewa.'), unit '.$laporan->lease->unit->kode_unit.'. Dilaporkan '.Format::tanggal($laporan->created_at).' pukul '.$laporan->created_at->format('H.i').'.'">
        {{ $laporan->labelKategori() }}
        <x-slot:aksi>
            <a href="{{ NomorHp::linkWhatsApp($penyewa->phone, $pesanWa) }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 rounded-[9px] bg-wa px-3.5 py-2.5 text-sm font-semibold text-white hover:bg-wa-hover">
                <svg viewBox="0 0 24 24" class="size-4 fill-white" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>
                Hubungi penyewa
            </a>
        </x-slot:aksi>
    </x-judul>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
        <section class="rounded-2xl border border-line bg-paper p-6">
            <x-laporan.tahap :laporan="$laporan" />
            <p class="mt-5 whitespace-pre-line text-[15px]">{{ $laporan->deskripsi }}</p>

            @if ($laporan->labelPreferensi())
                <p class="mt-4 text-sm text-muted">Penyewa ingin dikunjungi: <b class="text-ink">{{ $laporan->labelPreferensi() }}</b></p>
            @endif

            @if ($laporan->media->isNotEmpty())
                <div class="mt-5 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                    @foreach ($laporan->media as $foto)
                        <a href="{{ route('laporan.foto', $foto) }}" target="_blank" rel="noopener">
                            <img src="{{ route('laporan.foto', $foto) }}" alt="Foto kerusakan" loading="lazy" class="aspect-square w-full rounded-xl border border-line object-cover">
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="space-y-5 rounded-2xl border border-line bg-paper p-6 lg:self-start">
            @if ($laporan->status === StatusLaporan::Selesai)
                <p class="font-bold text-lunas">Perbaikan selesai {{ $laporan->selesai_at ? Format::tanggal($laporan->selesai_at) : '' }}</p>
                <p class="text-sm text-muted">
                    {{ $laporan->dikonfirmasi_at ? 'Penyewa sudah mengonfirmasi beres pada '.Format::tanggal($laporan->dikonfirmasi_at).'.' : 'Menunggu penyewa mengonfirmasi dari HP-nya.' }}
                </p>
            @else
                <form method="POST" action="{{ route('admin.laporan.jadwal', $laporan) }}" class="space-y-3" novalidate>
                    @csrf
                    <h2 class="font-bold">{{ $laporan->jadwal_kunjungan ? 'Ubah jadwal kunjungan' : 'Jadwalkan kunjungan' }}</h2>
                    <div class="grid grid-cols-2 gap-2.5">
                        <x-field label="Tanggal" name="tanggal" type="date" :min="now()->toDateString()" :value="$laporan->jadwal_kunjungan?->toDateString() ?? $laporan->preferensi_tanggal?->toDateString()" />
                        <x-field label="Jam" name="jam" type="time" :value="$laporan->jadwal_kunjungan?->format('H:i') ?? '09:00'" />
                    </div>
                    <x-tombol>Simpan jadwal</x-tombol>
                </form>

                <div class="flex flex-wrap gap-2 border-t border-line pt-5">
                    @if ($laporan->status !== StatusLaporan::Dikerjakan)
                        <form method="POST" action="{{ route('admin.laporan.status', $laporan) }}">
                            @csrf
                            <input type="hidden" name="status" value="dikerjakan">
                            <x-tombol jenis="sekunder">Tandai sedang dikerjakan</x-tombol>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.laporan.status', $laporan) }}" onsubmit="return confirm('Tandai perbaikan ini selesai?')">
                        @csrf
                        <input type="hidden" name="status" value="selesai">
                        <x-tombol class="bg-lunas! hover:bg-[#255a40]!">Tandai selesai</x-tombol>
                    </form>
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
