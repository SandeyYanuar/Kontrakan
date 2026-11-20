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
        {{-- HP: kartu. --}}
        <div class="space-y-2.5 md:hidden">
            @foreach ($penyewa as $orang)
                @php($lease = $orang->leaseAktif)
                @php($ktp = $orang->statusKtp())
                <a href="{{ route('admin.penyewa.show', $orang) }}" @class(['flex items-center gap-3 rounded-xl border border-line bg-paper p-3.5', 'opacity-70' => ! $orang->is_active])>
                    <span @class([
                        'grid size-[38px] shrink-0 place-items-center rounded-full text-[13px] font-extrabold',
                        'bg-roof text-white' => $lease,
                        'border border-dashed border-[#aeb7c2] text-muted' => ! $lease,
                    ])>{{ $lease?->unit->kode_unit ?? '–' }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold">{{ $orang->name }}</span>
                        <span class="block text-xs text-muted">{{ $orang->kode_penyewa }}, {{ $orang->is_active ? \App\Support\NomorHp::tampil($orang->phone) : 'sudah keluar' }}</span>
                    </span>
                    <span @class([
                        'shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold',
                        'bg-lunas-bg text-lunas' => $ktp === 'sah',
                        'bg-dicek-bg text-dicek' => $ktp === 'menunggu',
                        'bg-telat-bg text-telat' => $ktp === 'ditolak',
                        'bg-soft text-muted' => $ktp === 'belum_ada',
                    ])>{{ match ($ktp) { 'sah' => 'KTP sah', 'menunggu' => 'KTP dicek', 'ditolak' => 'KTP ulang', default => 'Tanpa KTP' } }}</span>
                </a>
            @endforeach
        </div>

        <div class="hidden overflow-x-auto rounded-2xl border border-line bg-paper md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-soft text-[13px] text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Penyewa</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Mulai sewa</th>
                        <th scope="col" class="px-4 py-3 font-semibold">KTP</th>
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
                                        'grid size-[38px] shrink-0 place-items-center rounded-full text-[13px] font-extrabold',
                                        'bg-roof text-white' => $lease,
                                        'border border-dashed border-[#aeb7c2] text-muted' => ! $lease,
                                    ])>{{ $lease?->unit->kode_unit ?? '–' }}</span>
                                    <div>
                                        <p class="font-semibold text-ink">
                                            <a href="{{ route('admin.penyewa.show', $orang) }}" class="underline-offset-2 hover:underline">{{ $orang->name }}</a>
                                            <span class="ml-1.5 whitespace-nowrap rounded-[5px] bg-[#e4e8ed] px-1.5 py-px align-[1px] text-[11px] font-bold tracking-wide text-roof">{{ $orang->kode_penyewa }}</span>
                                        </p>
                                        <p class="mt-0.5 text-xs text-muted">{{ \App\Support\NomorHp::tampil($orang->phone) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5">{{ $lease ? \App\Support\Format::tanggal($lease->tgl_mulai) : '' }}</td>
                            <td class="px-4 py-3.5">
                                @php($ktp = $orang->statusKtp())
                                <a href="{{ route('admin.penyewa.show', $orang) }}" @class([
                                    'inline-block whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-lunas-bg text-lunas' => $ktp === 'sah',
                                    'bg-dicek-bg text-dicek' => $ktp === 'menunggu',
                                    'bg-telat-bg text-telat' => $ktp === 'ditolak',
                                    'bg-soft text-muted' => $ktp === 'belum_ada',
                                ])>{{ match ($ktp) { 'sah' => 'KTP sah', 'menunggu' => 'KTP menunggu dicek', 'ditolak' => 'KTP perlu upload ulang', default => 'KTP belum ada' } }}</a>
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($orang->is_active)
                                    <span class="rounded-full bg-lunas-bg px-2.5 py-1 text-xs font-semibold text-lunas">Menyewa</span>
                                @else
                                    <span class="rounded-full bg-soft px-2.5 py-1 text-xs font-semibold text-muted">Sudah keluar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($orang->is_active)
                                    <x-tombol jenis="sekunder" :href="route('admin.penyewa.show', $orang)">Detail</x-tombol>
                                @else
                                    <x-tombol jenis="tenang" :href="route('admin.penyewa.show', $orang)">Detail</x-tombol>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
