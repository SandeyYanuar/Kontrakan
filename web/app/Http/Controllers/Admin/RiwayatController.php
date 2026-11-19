<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Enums\Peran;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\PeriodeBayar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Riwayat aktivitas: hanya membaca, tidak ada yang bisa diubah atau dihapus.
 */
class RiwayatController extends Controller
{
    public function index(Request $request): View
    {
        $bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->input('bulan')) === 1
            ? (string) $request->input('bulan')
            : PeriodeBayar::dari(CarbonImmutable::now());
        $jenis = array_key_exists((string) $request->input('jenis'), Aksi::pilihanKategori()) ? (string) $request->input('jenis') : null;
        $adminId = $request->integer('admin') ?: null;
        $cari = trim((string) $request->input('cari'));

        $awal = PeriodeBayar::awalBulan($bulan);

        $logs = ActivityLog::query()
            ->with(['actor', 'subject'])
            ->whereBetween('created_at', [$awal, $awal->endOfMonth()])
            ->when($jenis, fn (Builder $query) => $query->whereIn('aksi', Aksi::dalamKategori($jenis)))
            ->when($adminId, fn (Builder $query) => $query->where('actor_id', $adminId))
            ->when($cari !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($cari): void {
                $penyewaCocok = User::query()
                    ->where('name', 'like', "%{$cari}%")
                    ->orWhere('kode_penyewa', 'like', "%{$cari}%")
                    ->pluck('kode_penyewa')
                    ->filter();

                $query->where('data', 'like', '%"'.$cari.'%')
                    ->orWhereIn('actor_id', User::query()->where('name', 'like', "%{$cari}%")->select('id'));

                foreach ($penyewaCocok as $kode) {
                    $query->orWhere('data', 'like', '%"kode_penyewa":"'.$kode.'"%');
                }
            }))
            ->latest('created_at')
            ->latest('id')
            ->paginate(100)
            ->withQueryString();

        return view('admin.riwayat', [
            'logs' => $logs,
            'bulan' => $bulan,
            'jenis' => $jenis,
            'adminId' => $adminId,
            'cari' => $cari,
            'pengelola' => User::query()->whereIn('role', [Peran::SuperAdmin, Peran::Admin])->orderBy('name')->get(),
            'pilihanBulan' => collect(range(0, 11))->map(fn (int $mundur): string => CarbonImmutable::now()->startOfMonth()->subMonths($mundur)->format('Y-m')),
        ]);
    }
}
