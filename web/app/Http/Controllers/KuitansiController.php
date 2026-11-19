<?php

namespace App\Http\Controllers;

use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Pengaturan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kuitansi PDF untuk tagihan yang sudah lunas. Penyewa hanya untuk tagihannya sendiri; admin untuk semua.
 */
class KuitansiController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice): Response
    {
        $user = $request->user();

        abort_unless($user->isPengelola() || $invoice->lease->user_id === $user->id, 404);
        abort_unless($invoice->status === StatusTagihan::Lunas, 404);

        $pembayaran = $invoice->payments()
            ->where('status', Payment::DISETUJUI)
            ->with('pemeriksa')
            ->latest('reviewed_at')
            ->first();

        $penyewa = $invoice->lease->penyewa;
        $nomor = 'KW/'.str_replace('-', '', $invoice->periode).'/'.$penyewa->kode_penyewa;

        $pdf = Pdf::loadView('kuitansi', [
            'invoice' => $invoice->load('lease.unit', 'items'),
            'penyewa' => $penyewa,
            'pembayaran' => $pembayaran,
            'nomor' => $nomor,
            'pengaturan' => Pengaturan::semua(),
        ])->setPaper('a5', 'landscape');

        return $pdf->download('kuitansi-'.$penyewa->kode_penyewa.'-'.$invoice->periode.'.pdf');
    }
}
