<x-layouts.admin title="Unit" aktif="unit">
    <x-judul keterangan="Harga sewa di sini dipakai untuk tagihan berikutnya. Hanya super admin yang bisa menambah atau mengubah unit.">
        Unit
        @can('super-admin')
            <x-slot:aksi>
                <x-tombol :href="route('admin.unit.create')">Tambah unit</x-tombol>
            </x-slot:aksi>
        @endcan
    </x-judul>

    @if ($units->isEmpty())
        <div class="rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
            <p class="font-semibold">Belum ada unit</p>
            <p class="mt-1 text-sm text-muted">
                @can('super-admin')
                    Tambahkan unit pertama, misalnya A1 dengan harga sewanya.
                @else
                    Super admin belum menambahkan unit.
                @endcan
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line bg-paper">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-soft text-[13px] text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Unit</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Harga sewa per bulan</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Ditempati</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Keterangan</th>
                        @can('super-admin')
                            <th scope="col" class="w-px px-4 py-3 font-semibold">Tindakan</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($units as $unit)
                        <tr>
                            <td class="px-4 py-3.5">
                                <span class="inline-grid size-9 place-items-center rounded-lg bg-roof text-[13px] font-extrabold text-white">{{ $unit->kode_unit }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold">{{ \App\Support\Format::rupiah($unit->harga_sewa) }}</td>
                            <td class="px-4 py-3.5">
                                @if ($unit->leaseAktif)
                                    {{ $unit->leaseAktif->penyewa->name }}
                                    <span class="text-xs text-muted">{{ $unit->leaseAktif->penyewa->kode_penyewa }}</span>
                                @else
                                    <span class="text-muted">Kosong</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-muted">{{ $unit->keterangan ?? '' }}</td>
                            @can('super-admin')
                                <td class="px-4 py-3.5">
                                    <x-tombol jenis="sekunder" :href="route('admin.unit.edit', $unit)">Ubah</x-tombol>
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
