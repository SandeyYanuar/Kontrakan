<x-layouts.admin title="Pengaturan" aktif="pengaturan">
    <x-judul keterangan="Hanya super admin yang bisa mengubah pengaturan ini. Setiap perubahan tercatat di riwayat aktivitas.">Pengaturan</x-judul>

    <form method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-8" novalidate>
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
                <p class="mt-1 text-sm text-muted">Sekarang: tanggal {{ \App\Models\Pengaturan::keteranganPeriodeBayar() }}. Selama periode ini tagihan yang belum lunas berstatus jatuh tempo dekat, dan sehari setelah jatuh tempo berstatus telat. Kalau tanggal mulai lebih besar dari jatuh tempo, tanggal mulai dihitung di bulan sebelumnya.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Mulai bayar tanggal" name="tanggal_mulai_bayar" type="number" min="1" max="28" :value="$pengaturan['tanggal_mulai_bayar']" hint="Contoh: 27 berarti mulai tanggal 27 bulan sebelumnya." required />
                <x-field label="Jatuh tempo tanggal" name="tanggal_jatuh_tempo" type="number" min="1" max="28" :value="$pengaturan['tanggal_jatuh_tempo']" required />
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-line bg-paper p-6">
            <div>
                <h2 class="text-lg font-bold">Foto latar dashboard</h2>
                <p class="mt-1 text-sm text-muted">Tampil di belakang menu kaca dashboard admin. Pakai foto kontrakan sendiri supaya terasa milik sendiri. Tidak terlihat oleh penyewa.</p>
            </div>
            <img src="{{ $pengaturan['latar_dashboard'] ? route('admin.latar', ['v' => substr(md5($pengaturan['latar_dashboard']), 0, 8)]) : asset('img/latar-rumah.jpg') }}"
                alt="Foto latar yang dipakai sekarang" class="aspect-[16/7] w-full rounded-xl border border-line object-cover">
            <div>
                <label for="latar" class="mb-1.5 block text-sm font-semibold">Ganti foto <span class="font-normal text-muted">(opsional)</span></label>
                <input id="latar" name="latar" type="file" accept="image/jpeg,image/png" data-kecilkan
                    class="block w-full rounded-[10px] border border-dashed border-[#aeb7c2] bg-paper p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-dicek-bg file:px-3 file:py-2 file:font-semibold file:text-dicek">
                @error('latar')
                    <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
                @else
                    <p class="mt-1.5 text-[13px] text-muted">Foto mendatar (landscape) paling bagus. Foto besar otomatis dikecilkan.</p>
                @enderror
            </div>
            @if ($pengaturan['latar_dashboard'])
                <label class="flex items-center gap-2.5 text-sm">
                    <input type="checkbox" name="latar_bawaan" value="1" class="size-4 accent-dicek">
                    Kembalikan ke foto rumah bawaan
                </label>
            @endif
        </section>

        <x-tombol>Simpan pengaturan</x-tombol>
    </form>
</x-layouts.admin>
