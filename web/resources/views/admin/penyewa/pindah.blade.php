<x-layouts.admin :title="'Pindah unit '.$penyewa->name" aktif="penyewa">
    <x-judul keterangan="Sewa di unit {{ $penyewa->leaseAktif->unit->kode_unit }} berakhir sehari sebelum tanggal pindah, lalu sewa baru dimulai di unit tujuan. Kode {{ $penyewa->kode_penyewa }} tetap sama.">
        Pindahkan {{ $penyewa->name }}
    </x-judul>

    @if ($unitKosong->isEmpty())
        <div class="max-w-xl rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-8">
            <p class="font-semibold">Tidak ada unit kosong</p>
            <p class="mt-1 text-sm text-muted">Tambah unit atau akhiri sewa penyewa lain dulu.</p>
        </div>
    @else
        <form method="POST" action="{{ route('admin.penyewa.pindah', $penyewa) }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate>
            @csrf

            <x-pilihan label="Pindah ke unit" name="unit_id">
                <option value="">Pilih unit kosong</option>
                @foreach ($unitKosong as $unit)
                    <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->kode_unit }}, {{ \App\Support\Format::rupiah($unit->harga_sewa) }} per bulan</option>
                @endforeach
            </x-pilihan>
            <x-field label="Tanggal pindah" name="tgl_pindah" type="date" :value="now()->toDateString()" required />

            <div class="flex gap-2 pt-2">
                <x-tombol>Pindahkan</x-tombol>
                <x-tombol jenis="tenang" :href="route('admin.penyewa.index')">Batal</x-tombol>
            </div>
        </form>
    @endif
</x-layouts.admin>
