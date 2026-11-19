<x-layouts.admin title="Pengaturan" aktif="pengaturan">
    <x-judul keterangan="Hanya super admin yang bisa mengubah pengaturan ini. Setiap perubahan tercatat di riwayat aktivitas.">Pengaturan</x-judul>

    <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="max-w-2xl space-y-8" novalidate>
        @csrf
        @method('PUT')

        <section class="space-y-4 rounded-2xl border border-line bg-paper p-6">
            <h2 class="text-lg font-bold">Kontrakan</h2>
            <x-field label="Nama kontrakan" name="nama_kontrakan" :value="$pengaturan['nama_kontrakan']" hint="Tampil di menu, halaman login, dan kuitansi." required />
            <x-field
                label="Nomor WhatsApp pengelola"
                name="whatsapp_admin"
                type="tel"
                inputmode="tel"
                :value="$pengaturan['whatsapp_admin'] ? \App\Support\NomorHp::tampil($pengaturan['whatsapp_admin']) : null"
                hint="Nomor yang dihubungi penyewa lewat tombol Hubungi pengelola dan Lupa password. Kalau kosong, dipakai nomor super admin."
                opsional
            />
        </section>

        <section class="space-y-4 rounded-2xl border border-line bg-paper p-6">
            <div>
                <h2 class="text-lg font-bold">Rekening tujuan transfer</h2>
                <p class="mt-1 text-sm text-muted">Ditampilkan di beranda penyewa dengan tombol Salin.</p>
            </div>
            <x-field label="Nama bank" name="bank_nama" :value="$pengaturan['bank_nama']" opsional />
            <x-field label="Nomor rekening" name="bank_nomor" inputmode="numeric" :value="$pengaturan['bank_nomor']" opsional />
            <x-field label="Atas nama" name="bank_atas_nama" :value="$pengaturan['bank_atas_nama']" opsional />
        </section>

        <section class="space-y-4 rounded-2xl border border-line bg-paper p-6">
            <div>
                <h2 class="text-lg font-bold">Periode bayar</h2>
                <p class="mt-1 text-sm text-muted">Selama periode ini tagihan yang belum lunas berstatus jatuh tempo dekat. Sehari setelahnya berstatus telat.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Mulai bayar tanggal" name="tanggal_mulai_bayar" type="number" min="1" max="28" :value="$pengaturan['tanggal_mulai_bayar']" required />
                <x-field label="Jatuh tempo tanggal" name="tanggal_jatuh_tempo" type="number" min="1" max="28" :value="$pengaturan['tanggal_jatuh_tempo']" required />
            </div>
        </section>

        <x-tombol>Simpan pengaturan</x-tombol>
    </form>
</x-layouts.admin>
