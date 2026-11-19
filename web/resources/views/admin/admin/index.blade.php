<x-layouts.admin title="Admin" aktif="admin">
    <x-judul keterangan="Admin membantu mengecek bukti transfer, mengingatkan penyewa, dan menambah penyewa. Akun admin tidak dihapus, hanya dinonaktifkan, supaya namanya tetap ada di riwayat aktivitas.">
        Admin
        <x-slot:aksi>
            <x-tombol :href="route('admin.admin.create')">Tambah admin</x-tombol>
        </x-slot:aksi>
    </x-judul>

    {{-- HP: kartu. --}}
    <div class="space-y-2.5 md:hidden">
        @foreach ($pengelola as $orang)
            <div @class(['rounded-xl border border-line bg-paper p-3.5', 'opacity-70' => ! $orang->is_active])>
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $orang->name }}</p>
                        <p class="text-xs text-muted">{{ $orang->role->label() }}, {{ \App\Support\NomorHp::tampil($orang->phone) }}</p>
                    </div>
                    <span @class(['shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-lunas-bg text-lunas' => $orang->is_active, 'bg-soft text-muted' => ! $orang->is_active])>{{ $orang->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                @if ($orang->role === \App\Enums\Peran::Admin)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($orang->is_active)
                            <x-tombol jenis="sekunder" :href="route('admin.admin.reset-password.edit', $orang)">Reset password</x-tombol>
                            <form method="POST" action="{{ route('admin.admin.nonaktifkan', $orang) }}" onsubmit="return confirm('Nonaktifkan {{ $orang->name }}? Akun ini tidak bisa login sampai diaktifkan kembali.')">
                                @csrf
                                @method('PATCH')
                                <x-tombol jenis="bahaya">Nonaktifkan</x-tombol>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.admin.aktifkan', $orang) }}">
                                @csrf
                                @method('PATCH')
                                <x-tombol jenis="sekunder">Aktifkan kembali</x-tombol>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="hidden overflow-x-auto rounded-2xl border border-line bg-paper md:block">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-soft text-[13px] text-muted">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">Nama</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Peran</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                    <th scope="col" class="w-px px-4 py-3 font-semibold">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($pengelola as $orang)
                    <tr @class(['text-muted' => ! $orang->is_active])>
                        <td class="px-4 py-3.5">
                            <p class="font-semibold text-ink">{{ $orang->name }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ \App\Support\NomorHp::tampil($orang->phone) }}</p>
                        </td>
                        <td class="px-4 py-3.5">{{ $orang->role->label() }}</td>
                        <td class="px-4 py-3.5">
                            @if ($orang->is_active)
                                <span class="rounded-full bg-lunas-bg px-2.5 py-1 text-xs font-semibold text-lunas">Aktif</span>
                            @else
                                <span class="rounded-full bg-soft px-2.5 py-1 text-xs font-semibold text-muted">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            @if ($orang->role === \App\Enums\Peran::Admin)
                                <div class="flex gap-2">
                                    @if ($orang->is_active)
                                        <x-tombol jenis="sekunder" :href="route('admin.admin.reset-password.edit', $orang)">Reset password</x-tombol>
                                        <form method="POST" action="{{ route('admin.admin.nonaktifkan', $orang) }}" onsubmit="return confirm('Nonaktifkan {{ $orang->name }}? Akun ini tidak bisa login sampai diaktifkan kembali.')">
                                            @csrf
                                            @method('PATCH')
                                            <x-tombol jenis="bahaya">Nonaktifkan</x-tombol>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.admin.aktifkan', $orang) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-tombol jenis="sekunder">Aktifkan kembali</x-tombol>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
