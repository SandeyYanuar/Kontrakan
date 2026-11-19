<?php

namespace App\Support;

use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Lease;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Satu baris di halaman Tagihan admin: satu penyewa aktif dengan tagihan bulan terpilih dan tunggakannya.
 */
class BarisTagihan
{
    /**
     * Urutan dari yang paling mendesak. Dipakai untuk warna pintu dan urutan baris.
     */
    public const URUTAN = [
        Invoice::LABEL_TELAT,
        Invoice::LABEL_DITOLAK,
        Invoice::LABEL_MENUNGGU,
        Invoice::LABEL_DEKAT,
        Invoice::LABEL_BELUM_PERIODE,
        self::TANPA_TAGIHAN,
        Invoice::LABEL_LUNAS,
    ];

    public const TANPA_TAGIHAN = 'tanpa_tagihan';

    public const KELOMPOK_TINDAK = 'tindak';

    public const KELOMPOK_BELUM_WAKTUNYA = 'belum_waktunya';

    public const KELOMPOK_LUNAS = 'lunas';

    /**
     * @param  Collection<int, Invoice>  $terbuka  tagihan sampai bulan terpilih yang belum lunas, terlama dulu
     */
    public function __construct(
        public readonly Lease $lease,
        public readonly ?Invoice $bulanIni,
        public readonly Collection $terbuka,
        public readonly CarbonImmutable $hariIni,
    ) {}

    /**
     * Label paling mendesak di antara tagihan yang belum lunas.
     */
    public function status(): string
    {
        if ($this->terbuka->isEmpty()) {
            return $this->bulanIni ? Invoice::LABEL_LUNAS : self::TANPA_TAGIHAN;
        }

        return $this->terbuka
            ->map(fn (Invoice $invoice): string => $invoice->label($this->hariIni))
            ->sortBy(fn (string $label): int => array_search($label, self::URUTAN, true))
            ->first();
    }

    public function kelompok(): string
    {
        return match ($this->status()) {
            Invoice::LABEL_LUNAS => self::KELOMPOK_LUNAS,
            Invoice::LABEL_BELUM_PERIODE, self::TANPA_TAGIHAN => self::KELOMPOK_BELUM_WAKTUNYA,
            default => self::KELOMPOK_TINDAK,
        };
    }

    public function peringkat(): int
    {
        return $this->lewatBatasTunggakan() ? -1 : (int) array_search($this->status(), self::URUTAN, true);
    }

    /**
     * Lebih dari 1 bulan tagihan yang belum dibayar dan sudah lewat jatuh tempo.
     */
    public function lewatBatasTunggakan(): bool
    {
        return AturanTunggakan::lewatBatas($this->terbuka, $this->hariIni);
    }

    /**
     * Tagihan yang masih harus dibayar penyewa (belum bayar atau ditolak), terlama dulu.
     *
     * @return Collection<int, Invoice>
     */
    public function harusDibayar(): Collection
    {
        return $this->terbuka->filter(fn (Invoice $invoice): bool => $invoice->status->belumLunas())->values();
    }

    public function totalHarusDibayar(): int
    {
        return (int) $this->harusDibayar()->sum('total');
    }

    /**
     * Tagihan dengan bukti transfer yang menunggu dicek admin.
     *
     * @return Collection<int, Invoice>
     */
    public function menungguDicek(): Collection
    {
        return $this->terbuka->filter(fn (Invoice $invoice): bool => $invoice->status === StatusTagihan::Menunggu)->values();
    }

    /**
     * Tunggakan: tagihan bulan sebelumnya yang belum lunas.
     *
     * @return Collection<int, Invoice>
     */
    public function tunggakan(): Collection
    {
        return $this->terbuka->filter(fn (Invoice $invoice): bool => $invoice->periode !== $this->bulanIni?->periode)->values();
    }

    /**
     * Teks status singkat untuk tabel, misalnya "Telat 4 hari" atau "Jatuh tempo 3 hari lagi".
     */
    public function teksStatus(): string
    {
        $paling = $this->terbuka->first(fn (Invoice $invoice): bool => $invoice->label($this->hariIni) === $this->status());

        return match ($this->status()) {
            Invoice::LABEL_TELAT => 'Telat '.$paling->hariTelat($this->hariIni).' hari',
            Invoice::LABEL_DITOLAK => 'Bukti ditolak',
            Invoice::LABEL_MENUNGGU => 'Bukti perlu dicek',
            Invoice::LABEL_DEKAT => $this->teksDekat($paling),
            Invoice::LABEL_BELUM_PERIODE => 'Bayar mulai '.Format::tanggalPendek(PeriodeBayar::mulaiBayar($paling->periode)),
            self::TANPA_TAGIHAN => 'Belum ada tagihan',
            default => 'Lunas',
        };
    }

    /**
     * Pesan WhatsApp pengingat yang sudah terisi.
     */
    public function pesanPengingat(string $linkWeb): string
    {
        $penyewa = $this->lease->penyewa;
        $tagihan = $this->harusDibayar();
        $rincian = $tagihan
            ->map(fn (Invoice $invoice): string => '- '.Format::bulan($invoice->periode).': '.Format::rupiah($invoice->total))
            ->implode("\n");
        $jatuhTempo = $tagihan->first()->jatuh_tempo;
        $sudahLewat = $this->hariIni->greaterThan($jatuhTempo);

        return "Halo {$penyewa->name} ({$penyewa->kode_penyewa}), ini pengingat sewa kontrakan unit {$this->lease->unit->kode_unit}:\n"
            .$rincian."\n"
            .($tagihan->count() > 1 ? 'Total '.Format::rupiah($this->totalHarusDibayar()).".\n" : '')
            .($sudahLewat
                ? 'Jatuh temponya sudah lewat ('.Format::tanggal($jatuhTempo).").\n"
                : 'Jatuh tempo '.Format::tanggal($jatuhTempo).".\n")
            .($this->lewatBatasTunggakan() ? 'Tunggakan sudah melewati batas '.AturanTunggakan::BATAS_BULAN." bulan. Mohon segera dilunasi.\n" : '')
            ."Mohon tulis {$penyewa->kode_penyewa} di berita transfer, lalu upload buktinya di {$linkWeb}\n"
            .'Terima kasih.';
    }

    private function teksDekat(Invoice $invoice): string
    {
        $sisa = (int) $this->hariIni->diffInDays($invoice->jatuh_tempo);

        return $sisa === 0 ? 'Jatuh tempo hari ini' : "Jatuh tempo {$sisa} hari lagi";
    }
}
