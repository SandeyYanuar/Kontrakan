<x-layouts.admin :title="'Reset password '.$penyewa->name" aktif="penyewa">
    <x-judul keterangan="Password lama {{ $penyewa->name }} ({{ $penyewa->kode_penyewa }}) langsung tidak berlaku. Reset ini tercatat di riwayat aktivitas.">Reset password {{ $penyewa->name }}</x-judul>

    <form method="POST" action="{{ route('admin.penyewa.reset-password', $penyewa) }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate>
        @csrf
        @method('PATCH')

        <x-field label="Password baru" name="password" type="password" autocomplete="new-password" hint="Minimal 8 karakter." required />
        <x-field label="Ulangi password baru" name="password_confirmation" type="password" autocomplete="new-password" required />

        <div class="flex gap-2 pt-2">
            <x-tombol>Reset password</x-tombol>
            <x-tombol jenis="tenang" :href="route('admin.penyewa.index')">Batal</x-tombol>
        </div>
    </form>
</x-layouts.admin>
