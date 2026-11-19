<x-layouts.admin title="Tambah admin" aktif="admin">
    <x-judul keterangan="Admin login dengan nomor WhatsApp dan password awal ini. Beri tahu password awalnya lewat WhatsApp, lalu minta admin menggantinya.">Tambah admin</x-judul>

    <form method="POST" action="{{ route('admin.admin.store') }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate>
        @csrf

        <x-field label="Nama" name="name" autocomplete="off" required />
        <x-field label="Nomor HP WhatsApp" name="phone" type="tel" inputmode="tel" placeholder="0812 3456 7890" hint="Diawali 08 atau 62." required />
        <x-field label="Email" name="email" type="email" opsional />
        <x-field label="Password awal" name="password" type="password" autocomplete="new-password" hint="Minimal 8 karakter." required />
        <x-field label="Ulangi password awal" name="password_confirmation" type="password" autocomplete="new-password" required />

        <div class="flex gap-2 pt-2">
            <x-tombol>Tambah admin</x-tombol>
            <x-tombol jenis="tenang" :href="route('admin.admin.index')">Batal</x-tombol>
        </div>
    </form>
</x-layouts.admin>
