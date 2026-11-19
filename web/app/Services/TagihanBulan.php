<?php

namespace App\Services;

use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Unit;
use App\Support\BarisTagihan;
use App\Support\PeriodeBayar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Data halaman Tagihan admin untuk satu bulan.
 */
class TagihanBulan
{
    /**
     * Bulan yang ditampilkan kalau admin tidak memilih: bulan depan kalau periode bayarnya sudah dimulai
     * (misalnya 27 November menampilkan Desember), selain itu bulan ini.
     */
    public static function periodeAktif(?CarbonImmutable $hariIni = null): string
    {
        $hariIni = ($hariIni ?? CarbonImmutable::now())->startOfDay();
        $bulanDepan = PeriodeBayar::berikutnya(PeriodeBayar::dari($hariIni));

        return $hariIni->greaterThanOrEqualTo(PeriodeBayar::mulaiBayar($bulanDepan))
            ? $bulanDepan
            : PeriodeBayar::dari($hariIni);
    }

    /**
     * @return array{
     *     periode: string,
     *     baris: Collection<int, BarisTagihan>,
     *     units: Collection<int, array{unit: Unit, baris: ?BarisTagihan}>,
     *     ringkasan: array{tagihan: int, masuk: int, per_label: array<string, int>, tunggakan: int, jumlah_tunggakan: int}
     * }
     */
    public function untuk(string $periode, ?CarbonImmutable $hariIni = null): array
    {
        $hariIni = ($hariIni ?? CarbonImmutable::now())->startOfDay();

        $leases = Lease::query()
            ->aktif()
            ->with(['unit', 'penyewa', 'invoices' => fn ($query) => $query->where('periode', '<=', $periode)->orderBy('periode')])
            ->get();

        $baris = $leases
            ->map(fn (Lease $lease): BarisTagihan => new BarisTagihan(
                lease: $lease,
                bulanIni: $lease->invoices->firstWhere('periode', $periode),
                terbuka: $lease->invoices->reject(fn (Invoice $invoice): bool => $invoice->status === StatusTagihan::Lunas)->values(),
                hariIni: $hariIni,
            ))
            ->sortBy([
                fn (BarisTagihan $a, BarisTagihan $b): int => $a->peringkat() <=> $b->peringkat(),
                fn (BarisTagihan $a, BarisTagihan $b): int => strnatcmp($a->lease->unit->kode_unit, $b->lease->unit->kode_unit),
            ])
            ->values();

        $perUnit = $baris->keyBy(fn (BarisTagihan $item): int => $item->lease->unit_id);
        $units = Unit::query()->orderBy('kode_unit')->get()
            ->sortBy('kode_unit', SORT_NATURAL)
            ->values()
            ->map(fn (Unit $unit): array => ['unit' => $unit, 'baris' => $perUnit->get($unit->id)]);

        return [
            'periode' => $periode,
            'baris' => $baris,
            'units' => $units,
            'ringkasan' => $this->ringkasan($baris, $periode, $hariIni),
        ];
    }

    /**
     * @param  Collection<int, BarisTagihan>  $baris
     * @return array{tagihan: int, masuk: int, per_label: array<string, int>, tunggakan: int, jumlah_tunggakan: int}
     */
    private function ringkasan(Collection $baris, string $periode, CarbonImmutable $hariIni): array
    {
        $bulanIni = $baris->map->bulanIni->filter();
        $perLabel = [];

        foreach ($bulanIni as $invoice) {
            $label = $invoice->label($hariIni);
            $perLabel[$label] = ($perLabel[$label] ?? 0) + $invoice->total;
        }

        $tunggakan = $baris->flatMap(fn (BarisTagihan $item) => $item->tunggakan())
            ->filter(fn (Invoice $invoice): bool => $invoice->status->belumLunas());

        return [
            'tagihan' => (int) $bulanIni->sum('total'),
            'masuk' => (int) $bulanIni->where('status', StatusTagihan::Lunas)->sum('total'),
            'per_label' => $perLabel,
            'tunggakan' => (int) $tunggakan->sum('total'),
            'jumlah_tunggakan' => $tunggakan->count(),
        ];
    }
}
