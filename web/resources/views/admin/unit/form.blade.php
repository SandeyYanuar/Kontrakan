@php($baru = ! $unit->exists)

<x-layouts.admin :title="$baru ? 'Tambah unit' : 'Ubah unit '.$unit->kode_unit" aktif="unit">
    <x-judul :keterangan="$baru ? null : 'Perubahan harga berlaku untuk tagihan berikutnya yang belum dibuat. Tagihan yang sudah ada tidak berubah.'">
        {{ $baru ? 'Tambah unit' : 'Ubah unit '.$unit->kode_unit }}
    </x-judul>

    <form method="POST" action="{{ $baru ? route('admin.unit.store') : route('admin.unit.update', $unit) }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate>
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless

        <x-field label="Kode unit" name="kode_unit" :value="$unit->kode_unit" hint="Huruf dan angka, misalnya A1 atau B2." autocapitalize="characters" required />
        <x-field
            label="Harga sewa per bulan"
            name="harga_sewa"
            inputmode="numeric"
            :value="$unit->harga_sewa ? number_format($unit->harga_sewa, 0, ',', '.') : null"
            placeholder="750.000"
            hint="Ketik angkanya saja, titik boleh dipakai."
            required
        />
        <x-field label="Keterangan" name="keterangan" :value="$unit->keterangan" opsional />

        @php($warnaTerpilih = old('warna', $unit->warna ?? \App\Models\Unit::warnaBerikutnya()))
        <fieldset>
            <legend class="mb-2 text-sm font-semibold">Warna logo rumah</legend>
            <div class="flex flex-wrap gap-2.5">
                @foreach (\App\Models\Unit::PALET as $warna)
                    <label class="cursor-pointer rounded-xl p-1 has-checked:bg-dicek-bg has-checked:ring-2 has-checked:ring-dicek" title="{{ $warna }}">
                        <input type="radio" name="warna" value="{{ $warna }}" @checked($warnaTerpilih === $warna) class="sr-only">
                        <x-rumah warna="" :style="'color: '.$warna" class="h-9 w-10" />
                    </label>
                @endforeach
            </div>
            <p class="mt-1.5 text-[13px] text-muted">Dipakai sebagai logo unit di daftar Unit, Penyewa, dan Kerusakan.</p>
        </fieldset>

        <div class="flex gap-2 pt-2">
            <x-tombol>{{ $baru ? 'Tambah unit' : 'Simpan unit' }}</x-tombol>
            <x-tombol jenis="tenang" :href="route('admin.unit.index')">Batal</x-tombol>
        </div>
    </form>
</x-layouts.admin>
