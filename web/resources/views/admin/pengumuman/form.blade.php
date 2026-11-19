@php($baru = ! $pengumuman->exists)

<x-layouts.admin :title="$baru ? 'Buat pengumuman' : 'Ubah pengumuman'" aktif="pengumuman">
    <x-judul>{{ $baru ? 'Buat pengumuman' : 'Ubah pengumuman' }}</x-judul>

    <form method="POST" action="{{ $baru ? route('admin.pengumuman.store') : route('admin.pengumuman.update', $pengumuman) }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate>
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless

        <x-field label="Judul" name="judul" :value="$pengumuman->judul" placeholder="Contoh: Pemadaman air Sabtu pagi" required />
        <div>
            <label for="isi" class="mb-1.5 block text-sm font-semibold">Isi pengumuman</label>
            <textarea id="isi" name="isi" rows="5" @error('isi') aria-invalid="true" @enderror
                @class(['w-full rounded-[10px] border bg-paper px-3 py-2.5 text-[15px] focus:outline-3 focus:outline-dicek/30', 'border-telat' => $errors->has('isi'), 'border-[#cdd3da] focus:border-dicek' => ! $errors->has('isi')])>{{ old('isi', $pengumuman->isi) }}</textarea>
            @error('isi')
                <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Tampil mulai" name="tampil_mulai" type="date" :value="$pengumuman->tampil_mulai?->toDateString()" required />
            <x-field label="Tampil sampai" name="tampil_sampai" type="date" :value="$pengumuman->tampil_sampai?->toDateString()" hint="Kosongkan kalau tampil sampai diakhiri." opsional />
        </div>

        <div class="flex gap-2 pt-2">
            <x-tombol>{{ $baru ? 'Terbitkan' : 'Simpan' }}</x-tombol>
            <x-tombol jenis="tenang" :href="route('admin.pengumuman.index')">Batal</x-tombol>
        </div>
    </form>
</x-layouts.admin>
