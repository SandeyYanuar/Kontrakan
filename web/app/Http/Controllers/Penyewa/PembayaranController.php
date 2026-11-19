<?php

namespace App\Http\Controllers\Penyewa;

use App\Enums\Aksi;
use App\Enums\StatusTagihan;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Pengaturan;
use App\Support\Aktivitas;
use App\Support\AturanTunggakan;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyewa membayar tagihan dengan mengupload bukti transfer.
 */
class PembayaranController extends Controller
{
    public function create(Request $request, Invoice $invoice): View|RedirectResponse
    {
        $this->pastikanMilik($request, $invoice);

        if (! $invoice->bisaDibayar() || AturanTunggakan::penghalang($invoice)) {
            return redirect()->route('penyewa.beranda');
        }

        return view('penyewa.bayar', ['invoice' => $invoice, 'pengaturan' => Pengaturan::semua()]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->pastikanMilik($request, $invoice);

        if (! $invoice->bisaDibayar()) {
            return redirect()->route('penyewa.beranda')->with('status', 'Tagihan ini sudah lunas atau sedang dicek pengelola.');
        }

        if ($penghalang = AturanTunggakan::penghalang($invoice)) {
            return redirect()->route('penyewa.beranda')
                ->with('status', 'Lunasi tagihan '.Format::bulan($penghalang->periode).' dulu, baru tagihan ini bisa dibayar.');
        }

        $request->merge(['nominal_transfer' => Format::angka($request->input('nominal_transfer'))]);

        $data = $request->validate([
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'nominal_transfer' => ['required', 'integer', 'min:1'],
            'tgl_transfer' => ['required', 'date', 'before_or_equal:today'],
            'nama_pengirim' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'bukti.required' => 'Pilih foto atau screenshot bukti transfer.',
            'bukti.mimes' => 'Bukti harus berupa gambar JPG, PNG, atau file PDF.',
            'bukti.max' => 'Ukuran file maksimal 2 MB.',
            'nominal_transfer.required' => 'Isi nominal yang ditransfer.',
            'nominal_transfer.min' => 'Isi nominal yang ditransfer.',
            'tgl_transfer.required' => 'Isi tanggal transfer.',
            'tgl_transfer.before_or_equal' => 'Tanggal transfer tidak boleh setelah hari ini.',
        ]);

        $penyewa = $request->user();
        $path = $request->file('bukti')->store('bukti/'.$penyewa->id, 'local');

        DB::transaction(function () use ($invoice, $penyewa, $path, $data): void {
            $payment = $invoice->payments()->create([
                'user_id' => $penyewa->id,
                'file_path' => $path,
                'nominal_transfer' => (int) $data['nominal_transfer'],
                'tgl_transfer' => $data['tgl_transfer'],
                'nama_pengirim' => $data['nama_pengirim'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'status' => Payment::MENUNGGU,
            ]);

            $invoice->update(['status' => StatusTagihan::Menunggu]);

            Aktivitas::catat(Aksi::BuktiDiupload, $payment, [
                'kode_penyewa' => $penyewa->kode_penyewa,
                'unit' => $invoice->lease->unit->kode_unit,
                'periode' => $invoice->periode,
                'nominal' => (int) $data['nominal_transfer'],
            ]);
        });

        return redirect()->route('penyewa.beranda')->with('status', 'Bukti transfer terkirim. Pengelola akan mengecek rekening dulu.');
    }

    public function bukti(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($payment->user_id === $request->user()->id && Storage::disk('local')->exists($payment->file_path), 404);

        return Storage::disk('local')->response($payment->file_path, headers: ['Cache-Control' => 'private, no-store']);
    }

    private function pastikanMilik(Request $request, Invoice $invoice): void
    {
        abort_unless($invoice->lease->user_id === $request->user()->id, 404);
    }
}
