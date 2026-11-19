<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Enums\StatusTagihan;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Aktivitas;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin mengecek bukti transfer: setujui, tolak, atau (super admin) batalkan persetujuan.
 */
class PembayaranController extends Controller
{
    public function bukti(Payment $payment): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($payment->file_path), 404);

        return Storage::disk('local')->response($payment->file_path, headers: ['Cache-Control' => 'private, no-store']);
    }

    public function setujui(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== Payment::MENUNGGU) {
            return back()->with('status', 'Bukti ini sudah dicek sebelumnya.');
        }

        DB::transaction(function () use ($request, $payment): void {
            $payment->update(['status' => Payment::DISETUJUI, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            $payment->invoice->update(['status' => StatusTagihan::Lunas]);
            Aktivitas::catat(Aksi::PembayaranDisetujui, $payment, $this->dataLog($payment));
        });

        return back()->with('status', "Pembayaran {$this->nama($payment)} disetujui. Status jadi lunas.");
    }

    public function tolak(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== Payment::MENUNGGU) {
            return back()->with('status', 'Bukti ini sudah dicek sebelumnya.');
        }

        $request->merge(['payment_id' => $payment->id]);
        $data = $request->validate(
            ['alasan' => ['required', 'string', 'max:255']],
            ['alasan.required' => 'Tulis alasan penolakan supaya penyewa tahu apa yang harus diperbaiki.'],
        );

        DB::transaction(function () use ($request, $payment, $data): void {
            $payment->update([
                'status' => Payment::DITOLAK,
                'alasan' => $data['alasan'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            $payment->invoice->update(['status' => StatusTagihan::Ditolak]);
            Aktivitas::catat(Aksi::PembayaranDitolak, $payment, [...$this->dataLog($payment), 'alasan' => $data['alasan']]);
        });

        return back()->with('status', "Pembayaran {$this->nama($payment)} ditolak. Penyewa melihat alasannya di berandanya.");
    }

    /**
     * Super admin membatalkan persetujuan yang salah. Catatan pembayaran tetap ada dengan status dibatalkan.
     */
    public function batalkan(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === Payment::DISETUJUI, 404);

        $data = $request->validate(
            ['alasan' => ['required', 'string', 'max:255']],
            ['alasan.required' => 'Tulis alasan pembatalan untuk catatan riwayat.'],
        );

        DB::transaction(function () use ($payment, $data): void {
            $payment->update(['status' => Payment::DIBATALKAN, 'alasan' => $data['alasan']]);
            $payment->invoice->update(['status' => StatusTagihan::BelumBayar]);
            Aktivitas::catat(Aksi::PersetujuanDibatalkan, $payment, [...$this->dataLog($payment), 'alasan' => $data['alasan']]);
        });

        return back()->with('status', "Persetujuan pembayaran {$this->nama($payment)} dibatalkan. Tagihan kembali belum bayar.");
    }

    /**
     * @return array{kode_penyewa: ?string, unit: string, periode: string, nominal: int}
     */
    private function dataLog(Payment $payment): array
    {
        return [
            'kode_penyewa' => $payment->invoice->lease->penyewa->kode_penyewa,
            'unit' => $payment->invoice->lease->unit->kode_unit,
            'periode' => $payment->invoice->periode,
            'nominal' => $payment->nominal_transfer,
        ];
    }

    private function nama(Payment $payment): string
    {
        return $payment->invoice->lease->unit->kode_unit.' '.Format::bulan($payment->invoice->periode);
    }
}
