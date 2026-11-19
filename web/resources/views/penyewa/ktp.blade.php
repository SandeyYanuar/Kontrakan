@php($status = $identitas?->status ?? \App\Models\TenantIdentity::BELUM_ADA)

<x-layouts.penyewa title="Data KTP">
    <div class="p-4">
        <a href="{{ route('penyewa.beranda') }}" class="text-sm text-muted">Kembali</a>
        <h1 class="mt-3 text-[22px] font-extrabold tracking-tight">Data KTP</h1>
        <p class="mt-1 text-sm text-muted">Dipakai pengelola sebagai data identitas yang sah. Hanya kamu dan pengelola yang bisa melihatnya.</p>

        @session('status')
            <p class="mt-4 rounded-[10px] border border-lunas/30 bg-lunas-bg px-4 py-3 text-sm font-semibold text-lunas" role="status">{{ $value }}</p>
        @endsession

        @if ($identitas && $status !== \App\Models\TenantIdentity::BELUM_ADA)
            <div @class([
                'mt-4 rounded-xl border p-4',
                'border-lunas/30 bg-lunas-bg' => $status === 'sah',
                'border-dicek/30 bg-dicek-bg' => $status === 'menunggu',
                'border-telat/30 bg-telat-bg' => $status === 'ditolak',
            ])>
                <p @class([
                    'font-bold',
                    'text-lunas' => $status === 'sah',
                    'text-dicek' => $status === 'menunggu',
                    'text-telat' => $status === 'ditolak',
                ])>
                    @switch($status)
                        @case('sah') KTP sudah dinyatakan sah @break
                        @case('menunggu') KTP sedang dicek pengelola @break
                        @case('ditolak') Pengelola meminta upload ulang @break
                    @endswitch
                </p>
                @if ($status === 'ditolak')
                    <p class="mt-1 text-sm">Alasan: {{ $identitas->alasan }}</p>
                @endif
                @if ($identitas->foto_path)
                    <img src="{{ route('penyewa.ktp.foto') }}" alt="Foto KTP yang kamu upload" class="mt-3 w-full rounded-lg border border-line">
                @endif
                <p class="mt-3 text-sm">{{ $identitas->nama_ktp }}, NIK {{ \App\Support\Format::nikTersamar($identitas->nik) }}</p>
                @if ($status === 'sah')
                    <p class="mt-2 text-[13px] text-muted">Kalau KTP perlu diganti, hubungi pengelola.</p>
                @endif
            </div>
        @endif

        @if ($status !== \App\Models\TenantIdentity::SAH)
            <form method="POST" action="{{ route('penyewa.ktp.simpan') }}" enctype="multipart/form-data" class="mt-5 space-y-4 rounded-xl border border-line bg-paper p-4" novalidate>
                @csrf

                <div>
                    <label for="foto" class="mb-1.5 block text-sm font-semibold">Foto KTP</label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png" capture="environment"
                        @error('foto') aria-invalid="true" aria-describedby="foto-keterangan" @enderror
                        class="block w-full rounded-[10px] border border-dashed border-[#aeb7c2] bg-paper p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-dicek-bg file:px-3 file:py-2 file:font-semibold file:text-dicek">
                    <div id="foto-keterangan">
                        @error('foto')
                            <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-[13px] text-muted">JPG atau PNG, maksimal 2 MB. Pastikan semua tulisan terbaca.</p>
                        @enderror
                    </div>
                </div>

                <x-field label="NIK" name="nik" inputmode="numeric" autocomplete="off" maxlength="19" hint="16 angka, sama seperti di KTP." required />
                <x-field label="Nama sesuai KTP" name="nama_ktp" :value="$identitas?->nama_ktp ?? auth()->user()->name" required />

                <button type="submit" class="w-full rounded-xl bg-dicek px-4 py-3 text-[15px] font-bold text-white hover:bg-[#244274]">
                    {{ $status === 'ditolak' || $status === 'menunggu' ? 'Kirim ulang KTP' : 'Kirim KTP' }}
                </button>
            </form>
        @endif
    </div>
</x-layouts.penyewa>
