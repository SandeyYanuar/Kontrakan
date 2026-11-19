<x-layouts.penyewa title="Laporan kerusakan">
    <div class="p-4">
        <a href="{{ route('penyewa.beranda') }}" class="text-sm text-muted">Kembali</a>
        <div class="mt-3 flex items-end justify-between gap-3">
            <h1 class="text-[22px] font-extrabold tracking-tight">Laporan kerusakan</h1>
            <a href="{{ route('penyewa.laporan.create') }}" class="rounded-[10px] bg-dicek px-3.5 py-2.5 text-sm font-semibold text-white">Lapor baru</a>
        </div>

        @session('status')
            <p class="mt-4 rounded-[10px] border border-lunas/30 bg-lunas-bg px-4 py-3 text-sm font-semibold text-lunas" role="status">{{ $value }}</p>
        @endsession

        @forelse ($laporan as $item)
            <article class="mt-4 rounded-xl border border-line bg-paper p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-bold">{{ $item->labelKategori() }}</p>
                        <p class="text-xs text-muted">Dilaporkan {{ \App\Support\Format::tanggal($item->created_at) }}, unit {{ $item->lease->unit->kode_unit }}</p>
                    </div>
                    @if ($item->dikonfirmasi_at)
                        <span class="rounded-full bg-lunas-bg px-2.5 py-1 text-xs font-semibold text-lunas">Beres</span>
                    @endif
                </div>
                <p class="mt-2 text-sm">{{ $item->deskripsi }}</p>

                @if ($item->media->isNotEmpty())
                    <div class="mt-3 flex gap-2">
                        @foreach ($item->media as $foto)
                            <img src="{{ route('laporan.foto', $foto) }}" alt="Foto kerusakan" loading="lazy" class="size-16 rounded-lg border border-line object-cover">
                        @endforeach
                    </div>
                @endif

                <div class="mt-4"><x-laporan.tahap :laporan="$item" /></div>

                @if ($item->jadwal_kunjungan && $item->status === \App\Enums\StatusLaporan::Dijadwalkan)
                    <p class="mt-3 rounded-lg bg-dicek-bg px-3 py-2 text-sm text-dicek">
                        Teknisi datang <b>{{ $item->jadwal_kunjungan->locale('id')->translatedFormat('l, j F') }} pukul {{ $item->jadwal_kunjungan->format('H.i') }}</b>
                    </p>
                @endif

                @if ($item->status === \App\Enums\StatusLaporan::Selesai && ! $item->dikonfirmasi_at)
                    <form method="POST" action="{{ route('penyewa.laporan.konfirmasi', $item) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-[10px] bg-lunas px-4 py-3 text-sm font-bold text-white">Perbaikan sudah beres</button>
                        <p class="mt-1.5 text-center text-xs text-muted">Kalau belum beres, hubungi pengelola lewat WhatsApp.</p>
                    </form>
                @endif
            </article>
        @empty
            <div class="mt-4 rounded-xl border border-line bg-paper p-4">
                <p class="font-semibold">Belum ada laporan</p>
                <p class="mt-1 text-sm text-muted">Kalau ada yang rusak di unit kamu, laporkan di sini lengkap dengan fotonya.</p>
            </div>
        @endforelse
    </div>
</x-layouts.penyewa>
