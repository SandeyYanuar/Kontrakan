@php
    use App\Enums\Aksi;
    use App\Support\Format;
    use App\Support\KalimatAktivitas;

    $hariIni = now()->startOfDay();
    $judulHari = function ($tanggal) use ($hariIni): string {
        $hari = $tanggal->copy()->startOfDay();
        $teks = Format::tanggal($hari);

        return match (true) {
            $hari->equalTo($hariIni) => 'Hari ini, '.$teks,
            $hari->equalTo($hariIni->copy()->subDay()) => 'Kemarin, '.$teks,
            default => ucfirst($hari->locale('id')->translatedFormat('l')).', '.$teks,
        };
    };
    $perHari = $logs->getCollection()->groupBy(fn ($log) => $log->created_at->toDateString());

    $ikon = [
        'disetujui' => ['bg-lunas-bg', '<path d="M6 12.5l4 4L18 8" fill="none" stroke="#2e6a4c" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>'],
        'ditolak' => ['bg-telat-bg', '<path d="M7 7l10 10M17 7L7 17" fill="none" stroke="#a8261c" stroke-width="2.6" stroke-linecap="round"/>'],
        'pengingat' => ['bg-[#ddf5e6]', '<path fill="#1da851" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/>'],
        'bukti' => ['bg-dicek-bg', '<path d="M12 16V5M7 10l5-5 5 5M5 19h14" fill="none" stroke="#2c4f86" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>'],
        'laporan' => ['bg-dekat-bg', '<path d="M14.5 5.5a4 4 0 0 0-5 5L4 16l4 4 5.5-5.5a4 4 0 0 0 5-5l-2.5 2.5-2.5-.5-.5-2.5 2.5-2.5Z" fill="none" stroke="#b07415" stroke-width="2" stroke-linejoin="round"/>'],
        'ktp' => ['bg-dekat-bg', '<rect x="3" y="6" width="18" height="12" rx="2" fill="none" stroke="#b07415" stroke-width="2"/><path d="M7 10h4M7 14h6" stroke="#b07415" stroke-width="2" stroke-linecap="round"/>'],
        'penyewa' => ['bg-soft', '<circle cx="12" cy="8" r="3.5" fill="none" stroke="#5a6573" stroke-width="2"/><path d="M5 20c1-4 4-6 7-6s6 2 7 6" fill="none" stroke="#5a6573" stroke-width="2" stroke-linecap="round"/>'],
        'pengaturan' => ['bg-soft', '<circle cx="12" cy="12" r="3" fill="none" stroke="#5a6573" stroke-width="2"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3" stroke="#5a6573" stroke-width="2" stroke-linecap="round"/>'],
    ];
@endphp

<x-layouts.admin title="Riwayat aktivitas" aktif="riwayat">
    <x-judul keterangan="Catatan siapa yang menyetujui, menolak, mengingatkan, dan mengubah data. Catatan ini tidak bisa diubah atau dihapus.">Riwayat aktivitas</x-judul>

    <form method="GET" action="{{ route('admin.riwayat') }}" class="mb-5 flex flex-wrap gap-2.5" role="search">
        <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama, kode penyewa, atau unit" aria-label="Cari nama, kode penyewa, atau unit"
            class="min-w-0 flex-[1_1_220px] rounded-[9px] border border-[#cdd3da] bg-paper px-3 py-2.5 text-sm focus:border-dicek focus:outline-3 focus:outline-dicek/20">
        <select name="jenis" aria-label="Jenis aktivitas" class="rounded-[9px] border border-[#cdd3da] bg-paper px-3 py-2.5 text-sm">
            <option value="">Semua aktivitas</option>
            @foreach (Aksi::pilihanKategori() as $kunci => $label)
                <option value="{{ $kunci }}" @selected($jenis === $kunci)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="admin" aria-label="Pelaku" class="rounded-[9px] border border-[#cdd3da] bg-paper px-3 py-2.5 text-sm">
            <option value="">Semua admin</option>
            @foreach ($pengelola as $orang)
                <option value="{{ $orang->id }}" @selected($adminId === $orang->id)>{{ $orang->name }}</option>
            @endforeach
        </select>
        <select name="bulan" aria-label="Bulan" class="rounded-[9px] border border-[#cdd3da] bg-paper px-3 py-2.5 text-sm">
            @foreach ($pilihanBulan as $pilihan)
                <option value="{{ $pilihan }}" @selected($bulan === $pilihan)>{{ Format::bulan($pilihan) }}</option>
            @endforeach
        </select>
        <x-tombol jenis="sekunder">Terapkan</x-tombol>
    </form>

    @if ($perHari->isEmpty())
        <div class="rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
            <p class="font-semibold">Belum ada aktivitas</p>
            <p class="mt-1 text-sm text-muted">Tidak ada catatan untuk {{ Format::bulan($bulan) }} dengan filter ini.</p>
        </div>
    @endif

    @foreach ($perHari as $tanggal => $daftar)
        <h2 class="mt-6 mb-2 text-[13px] font-bold text-muted first:mt-0">{{ $judulHari(\Carbon\CarbonImmutable::parse($tanggal)) }}</h2>
        <ol class="overflow-hidden rounded-2xl border border-line bg-paper">
            @foreach ($daftar as $log)
                @php
                    $kalimat = new KalimatAktivitas($log);
                    [$latarIkon, $gambarIkon] = $ikon[$log->aksi->kategori()];
                    $bayar = $kalimat->pembayaran();
                @endphp
                <li class="grid grid-cols-[36px_minmax(0,1fr)] items-start gap-x-3.5 border-b border-line px-[18px] py-3.5 last:border-b-0 sm:grid-cols-[52px_36px_minmax(0,1fr)_auto]">
                    <time datetime="{{ $log->created_at->toIso8601String() }}" class="col-start-2 text-[13px] text-muted sm:col-start-1 sm:pt-2">{{ $log->created_at->format('H.i') }}</time>
                    <span class="{{ $latarIkon }} col-start-1 row-span-2 row-start-1 grid size-9 place-items-center rounded-full sm:col-start-2 sm:row-span-1" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="size-[18px]">{!! $gambarIkon !!}</svg>
                    </span>
                    <p class="col-start-2 text-sm leading-relaxed sm:col-start-3 sm:pt-[7px]">
                        <span class="inline-flex items-center gap-1.5 align-middle">
                            <span class="inline-grid size-5 place-items-center rounded-full bg-roof text-[10px] font-extrabold text-white">{{ mb_substr($kalimat->pelaku(), 0, 1) }}</span>
                            <b>{{ $kalimat->pelaku() }}</b>
                            @if ($kalimat->keteranganPelaku())
                                <span class="text-muted">({{ $kalimat->keteranganPelaku() }})</span>
                            @endif
                        </span>
                        {{ $kalimat->kalimat() }}.
                        @if ($kalimat->alasan())
                            <span class="mt-1.5 block rounded-r-lg border-l-3 border-telat bg-soft px-3 py-2 text-[13px] text-muted">Alasan: {{ $kalimat->alasan() }}</span>
                        @endif
                    </p>
                    @if ($bayar)
                        <a href="{{ route('admin.pembayaran.bukti', $bayar) }}" target="_blank" rel="noopener"
                            class="col-start-2 mt-2 justify-self-start whitespace-nowrap rounded-lg border border-line px-[11px] py-[7px] text-[13px] font-semibold text-dicek hover:bg-dicek-bg sm:col-start-4 sm:mt-[3px]">Lihat bukti</a>
                    @endif
                </li>
            @endforeach
        </ol>
    @endforeach

    @if ($logs->hasPages())
        <div class="mt-6">{{ $logs->links() }}</div>
    @endif
</x-layouts.admin>
