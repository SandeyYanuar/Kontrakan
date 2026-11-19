<x-layouts.admin title="Penyewa" aktif="penyewa">
    <x-judul keterangan="Setiap penyewa punya kode tetap yang tidak berubah walaupun pindah unit. Minta penyewa menulis kodenya di berita transfer.">
        Penyewa
        <x-slot:aksi>
            <x-tombol :href="route('admin.penyewa.create')">Tambah penyewa</x-tombol>
        </x-slot:aksi>
    </x-judul>

    @if ($penyewa->isEmpty())
        <div class="rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
            <p class="font-semibold">Belum ada penyewa</p>
            <p class="mt-1 text-sm text-muted">Tambahkan penyewa dan pilih unit kosong yang ditempatinya.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line bg-paper">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-soft text-[13px] text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Penyewa</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Mulai sewa</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                        <th scope="col" class="w-px px-4 py-3 font-semibold">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($penyewa as $orang)
                        @php($lease = $orang->leaseAktif)
                        <tr @class(['text-muted' => ! $orang->is_active])>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'grid size-[38px] shrink-0 place-items-center rounded-lg text-[13px] font-extrabold',
                                        'bg-roof text-white' => $lease,
                                        'border border-dashed border-[#aeb7c2] text-muted' => ! $lease,
                                    ])>{{ $lease?->unit->kode_unit ?? '–' }}</span>
                                    <div>
                                        <p class="font-semibold text-ink">
                                            {{ $orang->name }}
                                            <span class="ml-1.5 rounded-[5px] bg-[#e4e8ed] px-1.5 py-px align-[1px] text-[11px] font-bold tracking-wide text-roof">{{ $orang->kode_penyewa }}</span>
                                        </p>
                                        <p class="mt-0.5 text-xs text-muted">{{ \App\Support\NomorHp::tampil($orang->phone) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">{{ $lease ? \App\Support\Format::tanggal($lease->tgl_mulai) : '' }}</td>
                            <td class="px-4 py-3.5">
                                @if ($orang->is_active)
                                    <span class="rounded-full bg-lunas-bg px-2.5 py-1 text-xs font-semibold text-lunas">Menyewa</span>
                                @else
                                    <span class="rounded-full bg-soft px-2.5 py-1 text-xs font-semibold text-muted">Sudah keluar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($orang->is_active)
                                    <div class="flex gap-2">
                                        <x-tombol jenis="sekunder" :href="route('admin.penyewa.edit', $orang)">Ubah</x-tombol>
                                        <x-tombol jenis="sekunder" :href="route('admin.penyewa.reset-password.edit', $orang)">Reset password</x-tombol>
                                        @can('super-admin')
                                            @if ($lease)
                                                <x-tombol jenis="sekunder" :href="route('admin.penyewa.pindah.form', $orang)">Pindah unit</x-tombol>
                                                <x-tombol jenis="bahaya" :href="route('admin.penyewa.akhiri.form', $orang)">Akhiri sewa</x-tombol>
                                            @endif
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
