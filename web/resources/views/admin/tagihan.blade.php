@php
    use App\Models\Invoice;
    use App\Models\Pengaturan;
    use App\Support\BarisTagihan;
    use App\Support\Format;
    use App\Support\NomorHp;
    use App\Support\PeriodeBayar;
    use App\Support\WarnaStatus;

    $kelompok = $baris->groupBy(fn (BarisTagihan $b) => $b->kelompok());
    $terisi = $units->filter(fn ($u) => $u['baris'])->count();
    $sebelumnya = PeriodeBayar::awalBulan($periode)->subMonth()->format('Y-m');
    $berikutnya = PeriodeBayar::berikutnya($periode);

    /** Teks pendek di bawah pintu. */
    $ketPintu = fn (?BarisTagihan $b): string => match (true) {
        $b === null => 'Kosong',
        $b->status() === Invoice::LABEL_LUNAS => 'Lunas',
        $b->status() === Invoice::LABEL_MENUNGGU => 'Perlu dicek',
        $b->status() === Invoice::LABEL_DITOLAK => 'Ditolak',
        $b->status() === Invoice::LABEL_TELAT => 'Telat',
        $b->status() === Invoice::LABEL_DEKAT => 'Belum bayar',
        $b->status() === BarisTagihan::TANPA_TAGIHAN => 'Tanpa tagihan',
        default => 'Belum periode',
    };

    $segmen = [
        Invoice::LABEL_LUNAS => 'bg-lunas',
        Invoice::LABEL_MENUNGGU => 'bg-dicek',
        Invoice::LABEL_DEKAT => 'bg-dekat',
        Invoice::LABEL_BELUM_PERIODE => 'bg-belum',
        Invoice::LABEL_DITOLAK => 'bg-telat',
        Invoice::LABEL_TELAT => 'bg-telat',
    ];

    $judulKelompok = [
        BarisTagihan::KELOMPOK_TINDAK => 'Perlu ditindak',
        BarisTagihan::KELOMPOK_BELUM_WAKTUNYA => 'Belum waktunya bayar',
        BarisTagihan::KELOMPOK_LUNAS => 'Sudah lunas',
    ];
@endphp

<x-layouts.admin :title="'Tagihan '.Format::bulan($periode)" aktif="tagihan">
    {{-- Kepala halaman: bulan, navigasi bulan, dan ringkasan uang masuk. --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-6">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">Tagihan {{ Format::bulan($periode) }}</h1>
            <p class="mt-1.5 text-sm text-muted">
                {{ $terisi }} dari {{ $units->count() }} unit terisi. Periode bayar tanggal {{ Pengaturan::keteranganPeriodeBayar() }}.
            </p>
            <nav class="mt-3 flex gap-1 text-sm" aria-label="Pilih bulan">
                <a href="{{ route('admin.tagihan', ['bulan' => $sebelumnya]) }}" class="rounded-lg px-2.5 py-1.5 font-semibold text-dicek hover:bg-dicek-bg">{{ Format::bulan($sebelumnya) }}</a>
                <a href="{{ route('admin.tagihan', ['bulan' => $berikutnya]) }}" class="rounded-lg px-2.5 py-1.5 font-semibold text-dicek hover:bg-dicek-bg">{{ Format::bulan($berikutnya) }}</a>
            </nav>
        </div>

        <div class="w-full sm:w-auto sm:min-w-72">
            <p class="mb-2 text-[15px]">
                <b class="text-[22px] font-extrabold tracking-tight">{{ Format::rupiah($ringkasan['masuk']) }}</b>
                masuk dari {{ Format::rupiah($ringkasan['tagihan']) }}
            </p>
            <div class="flex h-2 overflow-hidden rounded-full bg-[#d6dbe2]" aria-hidden="true">
                @foreach ($segmen as $label => $kelas)
                    @if (($ringkasan['per_label'][$label] ?? 0) > 0)
                        <i class="{{ $kelas }} block h-full" style="width: {{ $ringkasan['per_label'][$label] / max($ringkasan['tagihan'], 1) * 100 }}%"></i>
                    @endif
                @endforeach
            </div>
            @if ($ringkasan['tunggakan'] > 0)
                <p class="mt-2 text-[13px] font-semibold text-telat">
                    Ditambah tunggakan bulan sebelumnya {{ Format::rupiah($ringkasan['tunggakan']) }} ({{ $ringkasan['jumlah_tunggakan'] }} tagihan)
                </p>
            @endif
        </div>
    </div>

    {{-- Deretan pintu: satu pintu per unit, warnanya status paling mendesak. --}}
    @if ($units->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-line bg-paper" aria-label="Deretan unit. Pilih pintu untuk melihat penyewanya.">
            <div class="atap relative h-[22px]"></div>
            <div class="flex overflow-x-auto px-3 pt-6">
                @foreach ($units as $item)
                    @php($b = $item['baris'])
                    <button type="button"
                        @if ($b) data-unit="{{ $item['unit']->kode_unit }}" @endif
                        class="group flex min-w-[92px] flex-1 flex-col items-center border-r border-dashed border-[#e3e7ec] px-2.5 last:border-r-0 {{ $b ? 'cursor-pointer' : 'cursor-default' }}"
                        aria-label="Unit {{ $item['unit']->kode_unit }}: {{ $b ? $b->lease->penyewa->name.', '.$b->teksStatus() : 'kosong' }}"
                        @unless ($b) tabindex="-1" @endunless>
                        <span @class([
                            'mb-2 h-[7px] w-3.5 rounded-b-lg',
                            'bg-[#f5c451] shadow-[0_4px_14px_2px_rgba(245,196,81,0.55)]' => $b,
                            'bg-[#c9cfd7]' => ! $b,
                        ])></span>
                        <span class="mb-2 rounded border border-line bg-soft px-[7px] py-px text-xs font-extrabold">{{ $item['unit']->kode_unit }}</span>
                        <span class="{{ $b ? WarnaStatus::latar($b->status()) : 'pintu-kosong' }} relative grid h-[104px] w-[58px] grid-rows-[1fr_1.3fr] gap-[7px] rounded-t px-2 py-[9px] transition-transform duration-200 group-hover:-translate-y-[3px] group-[.terpilih]:-translate-y-[3px]">
                            <i @class(['pintu-panel', '!bg-transparent' => ! $b])></i>
                            <i @class(['pintu-panel', '!bg-transparent' => ! $b])></i>
                            @if ($b)
                                <i class="absolute top-14 right-2 size-1.5 rounded-full bg-[#f2e6c8]"></i>
                            @endif
                        </span>
                        <span class="{{ $b ? WarnaStatus::teks($b->status()) : 'text-belum' }} mt-2.5 mb-0 h-[18px] text-xs font-semibold">{{ $ketPintu($b) }}</span>
                    </button>
                @endforeach
            </div>
            <div class="teras mt-2.5 h-3.5"></div>
        </div>
    @endif

    <h2 class="mt-8 mb-3 text-lg font-bold">Penyewa</h2>

    @if ($baris->isEmpty())
        <div class="rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
            <p class="font-semibold">Belum ada penyewa aktif</p>
            <p class="mt-1 text-sm text-muted">Tambahkan penyewa di menu Penyewa. Tagihan dibuat otomatis 14 hari sebelum jatuh tempo.</p>
        </div>
    @else
        {{-- Laptop: tabel. --}}
        <div class="hidden overflow-hidden rounded-2xl border border-line bg-paper md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-soft text-[13px] text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Penyewa</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Tagihan</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                        <th scope="col" class="w-px px-4 py-3 font-semibold">Tindakan</th>
                    </tr>
                </thead>
                @foreach ($judulKelompok as $kunci => $judul)
                    @continue(! $kelompok->has($kunci))
                    <tbody class="divide-y divide-line border-t border-line first-of-type:border-t-0">
                        <tr><th scope="rowgroup" colspan="4" class="bg-soft px-4 py-2 text-[13px] font-semibold text-muted">{{ $judul }} ({{ $kelompok[$kunci]->count() }})</th></tr>
                        @foreach ($kelompok[$kunci] as $b)
                            @php($penyewa = $b->lease->penyewa)
                            <tr data-baris="{{ $b->lease->unit->kode_unit }}" class="transition-colors duration-200 data-[terpilih]:bg-[#f3f7fd]">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="{{ WarnaStatus::latar($b->status()) }} grid size-[38px] shrink-0 place-items-center rounded-lg text-[13px] font-extrabold text-white">{{ $b->lease->unit->kode_unit }}</span>
                                        <div>
                                            <p class="font-semibold">
                                                {{ $penyewa->name }}
                                                <span class="ml-1.5 rounded-[5px] bg-[#e4e8ed] px-1.5 py-px align-[1px] text-[11px] font-bold tracking-wide text-roof">{{ $penyewa->kode_penyewa }}</span>
                                            </p>
                                            <p class="mt-0.5 text-xs text-muted">{{ NomorHp::tampil($penyewa->phone) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-semibold">
                                    @if ($b->harusDibayar()->isNotEmpty())
                                        {{ Format::rupiah($b->totalHarusDibayar()) }}
                                    @elseif ($b->bulanIni)
                                        {{ Format::rupiah($b->bulanIni->total) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="{{ WarnaStatus::label($b->status()) }} inline-block whitespace-nowrap rounded-full px-[11px] py-[5px] text-xs font-semibold">{{ $b->teksStatus() }}</span>
                                    @if ($b->tunggakan()->isNotEmpty())
                                        <p class="mt-1.5 text-xs text-telat">Termasuk tunggakan {{ $b->tunggakan()->map(fn ($i) => Format::bulan($i->periode))->implode(', ') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5"><x-tagihan.tombol-aksi :baris="$b" :periode="$periode" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>

        {{-- HP: kartu. --}}
        <div class="space-y-5 md:hidden">
            @foreach ($judulKelompok as $kunci => $judul)
                @continue(! $kelompok->has($kunci))
                <section>
                    <h3 class="mb-2 text-[13px] font-semibold text-muted">{{ $judul }} ({{ $kelompok[$kunci]->count() }})</h3>
                    <div class="space-y-2.5">
                        @foreach ($kelompok[$kunci] as $b)
                            @php($penyewa = $b->lease->penyewa)
                            <article data-baris="{{ $b->lease->unit->kode_unit }}" class="rounded-xl border border-line bg-paper p-3.5 data-[terpilih]:border-dicek">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <span class="{{ WarnaStatus::latar($b->status()) }} grid size-[38px] shrink-0 place-items-center rounded-lg text-[13px] font-extrabold text-white">{{ $b->lease->unit->kode_unit }}</span>
                                        <div>
                                            <p class="font-semibold">{{ $penyewa->name }}</p>
                                            <p class="text-xs text-muted">
                                                {{ $penyewa->kode_penyewa }}
                                                @if ($b->harusDibayar()->isNotEmpty())
                                                    , {{ Format::rupiah($b->totalHarusDibayar()) }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    @if ($kunci === BarisTagihan::KELOMPOK_LUNAS)
                                        <x-tagihan.tombol-aksi :baris="$b" :periode="$periode" />
                                    @else
                                        <span class="{{ WarnaStatus::label($b->status()) }} whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold">{{ $b->teksStatus() }}</span>
                                    @endif
                                </div>
                                @if ($b->tunggakan()->isNotEmpty())
                                    <p class="mt-2 text-xs text-telat">Termasuk tunggakan {{ $b->tunggakan()->map(fn ($i) => Format::bulan($i->periode))->implode(', ') }}</p>
                                @endif
                                @if ($kunci !== BarisTagihan::KELOMPOK_LUNAS && ($b->harusDibayar()->isNotEmpty() || $b->menungguDicek()->isNotEmpty()))
                                    <div class="mt-3"><x-tagihan.tombol-aksi :baris="$b" :periode="$periode" lebar /></div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <script>
        // Klik pintu: sorot baris penyewanya dan gulir ke sana.
        document.querySelectorAll('[data-unit]').forEach(function (pintu) {
            pintu.addEventListener('click', function () {
                var unit = pintu.dataset.unit;
                document.querySelectorAll('[data-unit], [data-baris]').forEach(function (el) {
                    el.classList.remove('terpilih');
                    el.removeAttribute('data-terpilih');
                });
                pintu.classList.add('terpilih');
                document.querySelectorAll('[data-baris="' + unit + '"]').forEach(function (el) {
                    el.setAttribute('data-terpilih', '');
                    if (el.offsetParent !== null) {
                        var tenang = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        el.scrollIntoView({ block: 'nearest', behavior: tenang ? 'auto' : 'smooth' });
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
