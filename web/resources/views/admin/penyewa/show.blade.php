@php
    use App\Models\TenantIdentity;
    use App\Support\Format;
    use App\Support\NomorHp;

    $identitas = $penyewa->identitas;
    $status = $penyewa->statusKtp();
@endphp

<x-layouts.admin :title="$penyewa->name" aktif="penyewa">
    <x-judul :keterangan="$penyewa->kode_penyewa.($penyewa->leaseAktif ? ', unit '.$penyewa->leaseAktif->unit->kode_unit.' sejak '.Format::tanggal($penyewa->leaseAktif->tgl_mulai) : ', sudah keluar')">
        {{ $penyewa->name }}
        <x-slot:aksi>
            @if ($penyewa->is_active)
                <x-tombol jenis="sekunder" :href="route('admin.penyewa.edit', $penyewa)">Ubah data</x-tombol>
                <x-tombol jenis="sekunder" :href="route('admin.penyewa.reset-password.edit', $penyewa)">Reset password</x-tombol>
                @can('super-admin')
                    @if ($penyewa->leaseAktif)
                        <x-tombol jenis="sekunder" :href="route('admin.penyewa.pindah.form', $penyewa)">Pindah unit</x-tombol>
                        <x-tombol jenis="bahaya" :href="route('admin.penyewa.akhiri.form', $penyewa)">Akhiri sewa</x-tombol>
                    @endif
                @endcan
            @endif
        </x-slot:aksi>
    </x-judul>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
        <section class="rounded-2xl border border-line bg-paper p-6">
            <h2 class="text-lg font-bold">Kontak</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-muted">Nomor HP WhatsApp</dt><dd class="font-semibold">{{ NomorHp::tampil($penyewa->phone) }}</dd></div>
                @if ($penyewa->phone_darurat)
                    <div><dt class="text-muted">Nomor darurat</dt><dd class="font-semibold">{{ NomorHp::tampil($penyewa->phone_darurat) }}</dd></div>
                @endif
                @if ($penyewa->email)
                    <div><dt class="text-muted">Email</dt><dd class="font-semibold">{{ $penyewa->email }}</dd></div>
                @endif
            </dl>

            <h2 class="mt-6 text-lg font-bold">Riwayat sewa</h2>
            <ul class="mt-3 space-y-1.5 text-sm">
                @foreach ($penyewa->leases->sortByDesc('tgl_mulai') as $lease)
                    <li>
                        Unit <b>{{ $lease->unit->kode_unit }}</b>, {{ Format::tanggal($lease->tgl_mulai) }}
                        sampai {{ $lease->tgl_selesai ? Format::tanggal($lease->tgl_selesai) : 'sekarang' }}
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-2xl border border-line bg-paper p-6 lg:col-start-1 lg:row-start-2">
            <h2 class="text-lg font-bold">Riwayat tagihan</h2>
            @if ($tagihan->isEmpty())
                <p class="mt-3 text-sm text-muted">Belum ada tagihan.</p>
            @else
                <ul class="mt-3 divide-y divide-line text-sm">
                    @foreach ($tagihan as $invoice)
                        @php($bayar = $invoice->paymentTerakhir)
                        <li class="py-3">
                            <div class="flex items-center justify-between gap-3">
                                <span><b>{{ Format::bulan($invoice->periode) }}</b> <span class="text-muted">unit {{ $invoice->lease->unit->kode_unit }}, {{ Format::rupiah($invoice->total) }}</span></span>
                                <span class="{{ \App\Support\WarnaStatus::label($invoice->label()) }} whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold">
                                    {{ match ($invoice->label()) { 'lunas' => 'Lunas', 'menunggu' => 'Perlu dicek', 'ditolak' => 'Ditolak', 'telat' => 'Telat', 'dekat' => 'Belum bayar', default => 'Belum periode' } }}
                                </span>
                            </div>
                            @if ($bayar && $bayar->status === \App\Models\Payment::DISETUJUI)
                                <p class="mt-1 text-xs text-muted">
                                    Disetujui {{ $bayar->pemeriksa?->name }}, {{ Format::tanggal($bayar->reviewed_at) }}.
                                    <a href="{{ route('kuitansi', $invoice) }}" class="font-semibold text-dicek">Kuitansi PDF</a>
                                </p>
                                @can('super-admin')
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-telat">Batalkan persetujuan</summary>
                                        <form method="POST" action="{{ route('admin.pembayaran.batalkan', $bayar) }}" class="mt-2 space-y-2" novalidate
                                              onsubmit="return confirm('Batalkan persetujuan {{ Format::bulan($invoice->periode) }}? Tagihan kembali belum bayar.')">
                                            @csrf
                                            <x-field label="Alasan pembatalan" name="alasan" placeholder="Contoh: uang ternyata belum masuk" />
                                            <x-tombol jenis="bahaya">Batalkan persetujuan</x-tombol>
                                        </form>
                                    </details>
                                @endcan
                            @elseif ($bayar && $bayar->status === \App\Models\Payment::DITOLAK)
                                <p class="mt-1 text-xs text-muted">Ditolak: {{ $bayar->alasan }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-2xl border border-line bg-paper p-6 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-start">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold">KTP</h2>
                <span @class([
                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                    'bg-lunas-bg text-lunas' => $status === TenantIdentity::SAH,
                    'bg-dicek-bg text-dicek' => $status === TenantIdentity::MENUNGGU,
                    'bg-telat-bg text-telat' => $status === TenantIdentity::DITOLAK,
                    'bg-soft text-muted' => $status === TenantIdentity::BELUM_ADA,
                ])>{{ $identitas?->labelStatus() ?? 'Belum ada' }}</span>
            </div>

            @if (! $identitas || $status === TenantIdentity::BELUM_ADA)
                <p class="mt-3 text-sm text-muted">Penyewa belum mengupload KTP. Penyewa mengisinya dari menu Profil di HP-nya.</p>
            @else
                @if ($identitas->foto_path)
                    <p class="mt-3 text-[13px] text-muted">Membuka foto KTP tercatat di riwayat aktivitas.</p>
                    <details class="mt-3 rounded-xl border border-line">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-dicek">Lihat foto KTP</summary>
                        <img src="{{ route('admin.penyewa.ktp.foto', $penyewa) }}" alt="Foto KTP {{ $penyewa->name }}" loading="lazy" class="w-full rounded-b-xl">
                    </details>
                @endif

                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="text-muted">Nama sesuai KTP</dt><dd class="font-semibold">{{ $identitas->nama_ktp }}</dd></div>
                    <div><dt class="text-muted">NIK</dt><dd class="font-semibold tracking-wide">{{ Format::nikTersamar($identitas->nik) }}</dd></div>
                    @if ($identitas->pemeriksa)
                        <div><dt class="text-muted">Dicek oleh</dt><dd>{{ $identitas->pemeriksa->name }}, {{ Format::tanggal($identitas->verified_at) }}</dd></div>
                    @endif
                    @if ($status === TenantIdentity::DITOLAK)
                        <div><dt class="text-muted">Alasan upload ulang</dt><dd>{{ $identitas->alasan }}</dd></div>
                    @endif
                </dl>

                @if ($status !== TenantIdentity::SAH)
                    <div class="mt-5 space-y-3 border-t border-line pt-5">
                        <form method="POST" action="{{ route('admin.penyewa.ktp.sah', $penyewa) }}">
                            @csrf
                            <x-tombol class="bg-lunas! hover:bg-[#255a40]!">Tandai sah</x-tombol>
                        </form>
                        <form method="POST" action="{{ route('admin.penyewa.ktp.tolak', $penyewa) }}" class="space-y-3" novalidate>
                            @csrf
                            <x-field label="Alasan minta upload ulang" name="alasan" placeholder="Contoh: foto buram, NIK tidak sama" />
                            <x-tombol jenis="bahaya">Minta upload ulang</x-tombol>
                        </form>
                    </div>
                @endif
            @endif

            @can('super-admin')
                @if (! $penyewa->is_active && $identitas && ($identitas->foto_path || $identitas->nik))
                    <form method="POST" action="{{ route('admin.penyewa.ktp.hapus', $penyewa) }}" class="mt-5 border-t border-line pt-5"
                          onsubmit="return confirm('Hapus foto dan NIK {{ $penyewa->name }}? Tindakan ini tidak bisa dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <p class="mb-3 text-sm text-muted">Penyewa ini sudah keluar. Foto dan NIK-nya bisa dihapus supaya data pribadinya tidak disimpan lebih lama dari perlu.</p>
                        <x-tombol jenis="bahaya">Hapus foto dan NIK</x-tombol>
                    </form>
                @endif
            @endcan
        </section>
    </div>
</x-layouts.admin>
