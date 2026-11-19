<x-layouts.admin :title="'Akhiri sewa '.$penyewa->name" aktif="penyewa">
    <x-judul keterangan="Unit {{ $penyewa->leaseAktif->unit->kode_unit }} menjadi kosong dan akun {{ $penyewa->name }} dinonaktifkan sehingga tidak bisa login. Riwayat bayarnya tetap tersimpan.">
        Akhiri sewa {{ $penyewa->name }}
    </x-judul>

    <form method="POST" action="{{ route('admin.penyewa.akhiri', $penyewa) }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate
          onsubmit="return confirm('Akhiri sewa {{ $penyewa->name }} di unit {{ $penyewa->leaseAktif->unit->kode_unit }}?')">
        @csrf

        <x-field label="Tanggal terakhir menempati" name="tgl_selesai" type="date" :value="now()->toDateString()" required />

        <div class="flex gap-2 pt-2">
            <x-tombol jenis="bahaya">Akhiri sewa</x-tombol>
            <x-tombol jenis="tenang" :href="route('admin.penyewa.index')">Batal</x-tombol>
        </div>
    </form>
</x-layouts.admin>
