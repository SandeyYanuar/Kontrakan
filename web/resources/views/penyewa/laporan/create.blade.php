@php use App\Enums\StatusLaporan; @endphp

<x-layouts.penyewa title="Lapor kerusakan">
    <div class="min-h-screen bg-paper p-4">
        <a href="{{ route('penyewa.beranda') }}" class="text-sm text-muted">Kembali</a>
        <h1 class="mt-3 text-[19px] font-extrabold tracking-tight">Lapor kerusakan</h1>
        <p class="mb-4 text-[13px] text-muted">Pengelola akan menjadwalkan kunjungan dan memberi kabar lewat WhatsApp.</p>

        <form method="POST" action="{{ route('penyewa.laporan.store') }}" enctype="multipart/form-data" class="space-y-4" novalidate>
            @csrf

            <fieldset>
                <legend class="mb-2 text-sm font-semibold">Jenis kerusakan</legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (StatusLaporan::kategori() as $kunci => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-[10px] border border-[#cdd3da] px-3 py-2.5 text-sm has-checked:border-dicek has-checked:bg-dicek-bg has-checked:font-semibold">
                            <input type="radio" name="kategori" value="{{ $kunci }}" @checked(old('kategori') === $kunci) class="accent-dicek">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('kategori')
                    <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
                @enderror
            </fieldset>

            <div>
                <label for="deskripsi" class="mb-1.5 block text-sm font-semibold">Ceritakan kerusakannya</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" placeholder="Contoh: keran kamar mandi bocor, air menetes terus sejak kemarin"
                    @error('deskripsi') aria-invalid="true" @enderror
                    @class(['w-full rounded-[10px] border bg-paper px-3 py-2.5 text-[15px] focus:outline-3 focus:outline-dicek/30', 'border-telat' => $errors->has('deskripsi'), 'border-[#cdd3da] focus:border-dicek' => ! $errors->has('deskripsi')])>{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="foto" class="mb-1.5 block text-sm font-semibold">Foto kerusakan <span class="font-normal text-muted">(opsional, maksimal 3)</span></label>
                <input id="foto" name="foto[]" type="file" accept="image/jpeg,image/png" multiple data-kecilkan
                    class="block w-full rounded-xl border-2 border-dashed border-[#aeb7c2] bg-paper p-4 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-dicek-bg file:px-3 file:py-2 file:font-semibold file:text-dicek">
                @if ($errors->has('foto') || $errors->has('foto.*'))
                    <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $errors->first('foto') ?: $errors->first('foto.*') }}</p>
                @else
                    <p class="mt-1.5 text-[13px] text-muted">Foto dari kamera otomatis dikecilkan sebelum dikirim.</p>
                @endif
            </div>

            <fieldset class="rounded-xl bg-soft p-3.5">
                <legend class="sr-only">Waktu kunjungan yang kamu inginkan</legend>
                <p class="mb-2 text-sm font-semibold">Kapan teknisi boleh datang? <span class="font-normal text-muted">(opsional)</span></p>
                <div class="grid grid-cols-2 gap-2">
                    <x-field label="Tanggal" name="preferensi_tanggal" type="date" :min="now()->toDateString()" />
                    <x-pilihan label="Waktu" name="preferensi_waktu">
                        <option value="">Kapan saja</option>
                        @foreach (StatusLaporan::waktu() as $kunci => $label)
                            <option value="{{ $kunci }}" @selected(old('preferensi_waktu') === $kunci)>{{ $label }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
            </fieldset>

            <button type="submit" class="w-full rounded-xl bg-dicek px-4 py-3.5 text-[15px] font-bold text-white hover:bg-[#244274]">Kirim laporan</button>
        </form>
    </div>
</x-layouts.penyewa>
