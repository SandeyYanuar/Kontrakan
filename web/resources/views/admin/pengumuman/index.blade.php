@php use App\Support\Format; @endphp

<x-layouts.admin title="Pengumuman" aktif="pengumuman">
    <x-judul keterangan="Pengumuman tampil di beranda semua penyewa selama periode tampilnya, misalnya jadwal pemadaman air, fogging, atau aturan parkir.">
        Pengumuman
        <x-slot:aksi>
            <x-tombol :href="route('admin.pengumuman.create')">Buat pengumuman</x-tombol>
        </x-slot:aksi>
    </x-judul>

    @if ($kelompok->isEmpty())
        <div class="rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
            <p class="font-semibold">Belum ada pengumuman</p>
            <p class="mt-1 text-sm text-muted">Buat pengumuman pertama, misalnya jadwal kerja bakti atau pemadaman air.</p>
        </div>
    @endif

    @foreach (['tampil' => 'Sedang tampil', 'terjadwal' => 'Terjadwal', 'berakhir' => 'Sudah berakhir'] as $kunci => $judul)
        @continue(! $kelompok->has($kunci))
        <h2 class="mt-6 mb-2.5 text-[13px] font-bold text-muted first:mt-0">{{ $judul }} ({{ $kelompok[$kunci]->count() }})</h2>
        <div class="space-y-2.5">
            @foreach ($kelompok[$kunci] as $item)
                <article @class(['rounded-xl border border-line bg-paper p-4', 'opacity-70' => $kunci === 'berakhir'])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-bold">{{ $item->judul }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm">{{ $item->isi }}</p>
                            <p class="mt-2 text-xs text-muted">
                                Tampil {{ Format::tanggal($item->tampil_mulai) }}
                                {{ $item->tampil_sampai ? 'sampai '.Format::tanggal($item->tampil_sampai) : 'sampai diakhiri' }},
                                oleh {{ $item->pembuat->name }}
                            </p>
                        </div>
                        @if ($kunci !== 'berakhir')
                            <div class="flex gap-2">
                                <x-tombol jenis="sekunder" :href="route('admin.pengumuman.edit', $item)">Ubah</x-tombol>
                                <form method="POST" action="{{ route('admin.pengumuman.akhiri', $item) }}" onsubmit="return confirm('Berhenti menampilkan pengumuman ini?')">
                                    @csrf
                                    <x-tombol jenis="bahaya">Akhiri</x-tombol>
                                </form>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endforeach
</x-layouts.admin>
