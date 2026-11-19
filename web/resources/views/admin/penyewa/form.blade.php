@php($baru = ! $penyewa->exists)

<x-layouts.admin :title="$baru ? 'Tambah penyewa' : 'Ubah '.$penyewa->name" aktif="penyewa">
    <x-judul :keterangan="$baru
        ? 'Kode penyewa dibuat otomatis. Bulan saat penyewa masuk dibayar lewat booking di luar aplikasi; tagihan di aplikasi mulai bulan berikutnya.'
        : 'Kode '.$penyewa->kode_penyewa.' tidak bisa diubah. Pindah unit dan akhiri sewa dilakukan super admin dari daftar penyewa.'">
        {{ $baru ? 'Tambah penyewa' : 'Ubah '.$penyewa->name }}
    </x-judul>

    @if ($baru && $unitKosong->isEmpty())
        <div class="max-w-xl rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-8">
            <p class="font-semibold">Semua unit sudah terisi</p>
            <p class="mt-1 text-sm text-muted">Penyewa baru butuh unit kosong. Super admin bisa menambah unit di menu Unit.</p>
        </div>
    @else
        <form method="POST" action="{{ $baru ? route('admin.penyewa.store') : route('admin.penyewa.update', $penyewa) }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate>
            @csrf
            @unless ($baru)
                @method('PUT')
            @endunless

            <x-field label="Nama" name="name" :value="$penyewa->name" autocomplete="off" required />
            <x-field label="Nomor HP WhatsApp" name="phone" type="tel" inputmode="tel" :value="$penyewa->phone ? \App\Support\NomorHp::tampil($penyewa->phone) : null" placeholder="0812 3456 7890" hint="Dipakai penyewa untuk login dan untuk tombol pengingat WhatsApp." required />
            <x-field label="Email" name="email" type="email" :value="$penyewa->email" opsional />

            @if ($baru)
                <x-pilihan label="Unit" name="unit_id" hint="Hanya unit kosong yang bisa dipilih.">
                    <option value="">Pilih unit kosong</option>
                    @foreach ($unitKosong as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->kode_unit }}, {{ \App\Support\Format::rupiah($unit->harga_sewa) }} per bulan</option>
                    @endforeach
                </x-pilihan>
                <x-field label="Tanggal mulai sewa" name="tgl_mulai" type="date" :value="now()->toDateString()" required />
                <x-field label="Password awal" name="password" type="password" autocomplete="new-password" hint="Minimal 8 karakter. Beri tahu penyewa lewat WhatsApp." required />
                <x-field label="Ulangi password awal" name="password_confirmation" type="password" autocomplete="new-password" required />
            @else
                <x-field label="Nomor HP darurat" name="phone_darurat" type="tel" inputmode="tel" :value="$penyewa->phone_darurat ? \App\Support\NomorHp::tampil($penyewa->phone_darurat) : null" opsional />
            @endif

            <div class="flex gap-2 pt-2">
                <x-tombol>{{ $baru ? 'Tambah penyewa' : 'Simpan' }}</x-tombol>
                <x-tombol jenis="tenang" :href="route('admin.penyewa.index')">Batal</x-tombol>
            </div>
        </form>
    @endif
</x-layouts.admin>
