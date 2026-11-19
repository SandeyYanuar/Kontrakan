@php use App\Support\Format; @endphp

<x-layouts.penyewa title="Upload bukti transfer">
    <div class="min-h-screen bg-paper p-4">
        <a href="{{ route('penyewa.beranda') }}" class="text-sm text-muted">Kembali</a>
        <h1 class="mt-3 text-[19px] font-extrabold tracking-tight">Upload bukti transfer</h1>
        <p class="mb-4 text-[13px] text-muted">Tagihan {{ Format::bulan($invoice->periode) }}, {{ Format::rupiah($invoice->total) }}</p>

        @if ($pengaturan['bank_nomor'])
            <p class="mb-4 rounded-lg bg-soft px-3 py-2.5 text-[13px] text-muted">
                Transfer ke <b class="text-ink">{{ $pengaturan['bank_nomor'] }}</b> ({{ $pengaturan['bank_nama'] }}, a.n. {{ $pengaturan['bank_atas_nama'] }})
                dan tulis <b class="text-ink">{{ auth()->user()->kode_penyewa }}</b> di berita transfer.
            </p>
        @endif

        <form method="POST" action="{{ route('penyewa.bayar.simpan', $invoice) }}" enctype="multipart/form-data" class="space-y-4" novalidate>
            @csrf

            <x-field label="Nominal yang ditransfer" name="nominal_transfer" inputmode="numeric" :value="number_format($invoice->total, 0, ',', '.')" required />
            <x-field label="Tanggal transfer" name="tgl_transfer" type="date" :value="now()->toDateString()" :max="now()->toDateString()" required />
            <x-field label="Nama pemilik rekening pengirim" name="nama_pengirim" hint="Isi kalau transfernya dari rekening orang lain." opsional />

            <div>
                <label for="bukti" class="mb-1.5 block text-sm font-semibold">Foto atau screenshot bukti</label>
                <input id="bukti" name="bukti" type="file" accept="image/jpeg,image/png,application/pdf" data-kecilkan
                    @error('bukti') aria-invalid="true" @enderror aria-describedby="bukti-keterangan"
                    class="block w-full rounded-xl border-2 border-dashed border-[#aeb7c2] bg-paper p-4 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-dicek-bg file:px-3 file:py-2 file:font-semibold file:text-dicek">
                <div id="bukti-keterangan">
                    @error('bukti')
                        <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-[13px] text-muted">JPG, PNG, atau PDF, maksimal 2 MB. Bisa langsung dari kamera atau galeri.</p>
                    @enderror
                </div>
            </div>

            <x-field label="Catatan untuk pengelola" name="catatan" opsional />

            <button type="submit" class="w-full rounded-xl bg-dicek px-4 py-3.5 text-[15px] font-bold text-white hover:bg-[#244274]">Kirim bukti transfer</button>
        </form>
    </div>
</x-layouts.penyewa>
