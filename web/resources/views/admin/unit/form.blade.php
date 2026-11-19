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

        <div class="flex gap-2 pt-2">
            <x-tombol>{{ $baru ? 'Tambah unit' : 'Simpan unit' }}</x-tombol>
            <x-tombol jenis="tenang" :href="route('admin.unit.index')">Batal</x-tombol>
        </div>
    </form>
</x-layouts.admin>
