@props(['invoice'])

@php
    use App\Support\Format;

    $payment = $invoice->paymentTerakhir;
    $penyewa = $invoice->lease->penyewa;
    $selisih = $payment->nominal_transfer - $invoice->total;
    $pdf = str_ends_with(strtolower($payment->file_path), '.pdf');
    $errorDiSini = (int) old('payment_id') === $payment->id && $errors->has('alasan');
@endphp

{{-- Pop-up cek bukti transfer: gambar di kiri, rincian dan tombol di kanan. --}}
<dialog id="bukti-{{ $invoice->id }}" data-buka-otomatis="{{ $errorDiSini ? '1' : '' }}"
    aria-labelledby="judul-bukti-{{ $invoice->id }}"
    class="m-auto w-[min(780px,calc(100vw-32px))] max-h-[calc(100vh-32px)] overflow-hidden rounded-2xl bg-paper p-0 text-ink backdrop:bg-ink/55">
    <div class="grid max-h-[calc(100vh-32px)] overflow-auto md:grid-cols-[300px_minmax(0,1fr)]">
        <div class="flex flex-col items-center gap-2.5 bg-[#e9ecf0] p-5">
            @if ($pdf)
                <div class="grid h-48 w-full place-items-center rounded-xl bg-paper text-sm text-muted">Bukti berupa file PDF</div>
            @else
                <img data-src="{{ route('admin.pembayaran.bukti', $payment) }}" alt="Bukti transfer {{ $penyewa->name }}" class="max-h-[60vh] w-full rounded-xl bg-paper object-contain shadow-sm">
            @endif
            <a href="{{ route('admin.pembayaran.bukti', $payment) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-dicek">
                {{ $pdf ? 'Buka file PDF' : 'Buka gambar ukuran penuh' }}
            </a>
        </div>

        <div class="flex flex-col p-6">
            <h2 id="judul-bukti-{{ $invoice->id }}" class="text-xl font-extrabold tracking-tight">Bukti transfer unit {{ $invoice->lease->unit->kode_unit }}</h2>
            <p class="mb-4 text-[13px] text-muted">
                {{ $penyewa->name }} ({{ $penyewa->kode_penyewa }}), diupload {{ Format::tanggal($payment->created_at) }} pukul {{ $payment->created_at->format('H.i') }}
            </p>

            <div class="mb-3.5 grid grid-cols-2 gap-2.5">
                <div class="rounded-[10px] border border-line bg-soft px-3 py-2.5">
                    <span class="block text-xs text-muted">Tagihan {{ Format::bulan($invoice->periode) }}</span>
                    <b class="text-base">{{ Format::rupiah($invoice->total) }}</b>
                </div>
                <div @class([
                    'rounded-[10px] border px-3 py-2.5',
                    'border-line bg-soft' => $selisih === 0,
                    'border-telat/40 bg-telat-bg' => $selisih < 0,
                    'border-dekat/40 bg-dekat-bg' => $selisih > 0,
                ])>
                    <span class="block text-xs text-muted">Nominal ditransfer</span>
                    <b class="text-base">{{ Format::rupiah($payment->nominal_transfer) }}</b>
                    @if ($selisih !== 0)
                        <span @class(['block text-xs font-semibold', 'text-telat' => $selisih < 0, 'text-dekat' => $selisih > 0])>
                            {{ $selisih < 0 ? 'Kurang '.Format::rupiah(-$selisih) : 'Lebih '.Format::rupiah($selisih) }}
                        </span>
                    @endif
                </div>
            </div>

            <dl class="mb-4 space-y-1 text-[13px]">
                <div class="flex gap-1.5"><dt class="text-muted">Tanggal transfer:</dt><dd class="font-semibold">{{ Format::tanggal($payment->tgl_transfer) }}</dd></div>
                @if ($payment->nama_pengirim)
                    <div class="flex gap-1.5"><dt class="text-muted">Rekening pengirim:</dt><dd class="font-semibold">{{ $payment->nama_pengirim }}</dd></div>
                @endif
                @if ($payment->catatan)
                    <div class="flex gap-1.5"><dt class="text-muted">Catatan penyewa:</dt><dd class="font-semibold">"{{ $payment->catatan }}"</dd></div>
                @endif
            </dl>
            <p class="mb-4 text-[13px] text-muted">Pastikan uangnya sudah masuk ke rekening sebelum menyetujui.</p>

            <form method="POST" action="{{ route('admin.pembayaran.tolak', $payment) }}" id="form-tolak-{{ $payment->id }}" novalidate>
                @csrf
                <div data-alasan @class(['mb-3.5', 'hidden' => ! $errorDiSini])>
                    <label for="alasan-{{ $payment->id }}" class="mb-1.5 block text-[13px] font-semibold">Alasan penolakan (dikirim ke penyewa)</label>
                    <textarea id="alasan-{{ $payment->id }}" name="alasan" rows="3"
                        placeholder="Contoh: nominal kurang Rp 50.000, atau gambar tidak terbaca"
                        @if ($errorDiSini) aria-invalid="true" aria-describedby="alasan-error-{{ $payment->id }}" @endif
                        @class(['w-full rounded-[10px] border p-2.5 text-sm focus:outline-3 focus:outline-telat/20', 'border-telat' => $errorDiSini, 'border-[#cdd3da] focus:border-telat' => ! $errorDiSini])>{{ $errorDiSini ? old('alasan') : '' }}</textarea>
                    @if ($errorDiSini)
                        <p id="alasan-error-{{ $payment->id }}" class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $errors->first('alasan') }}</p>
                    @endif
                </div>
            </form>

            <div class="mt-auto flex flex-wrap gap-2.5">
                <form method="POST" action="{{ route('admin.pembayaran.setujui', $payment) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full rounded-[10px] bg-lunas px-4 py-3 text-sm font-semibold text-white hover:bg-[#255a40]">Setujui pembayaran</button>
                </form>
                <button type="button" data-tolak="{{ $payment->id }}" data-siap="{{ $errorDiSini ? '1' : '' }}"
                    @class(['rounded-[10px] border border-telat px-4 py-3 text-sm font-semibold', 'bg-telat text-white' => $errorDiSini, 'bg-paper text-telat' => ! $errorDiSini])>
                    {{ $errorDiSini ? 'Kirim penolakan' : 'Tolak' }}
                </button>
                <button type="button" data-tutup class="rounded-[10px] px-4 py-3 text-sm font-semibold text-muted hover:bg-soft">Batal</button>
            </div>
        </div>
    </div>
</dialog>
