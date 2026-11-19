@php use App\Support\Format; @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kuitansi {{ $nomor }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #18222f; font-size: 11px; margin: 0; }
        .atap { height: 14px; background: #3b4656; }
        .isi { padding: 22px 32px 18px; }
        .kepala { width: 100%; border-collapse: collapse; }
        .kepala td { vertical-align: bottom; }
        .pintu { width: 26px; height: 42px; background: #2e6a4c; border-radius: 2px 2px 0 0; }
        .nama { font-size: 18px; font-weight: bold; }
        .judul { font-size: 10px; color: #5a6573; }
        .nomor { text-align: right; font-size: 10px; color: #5a6573; }
        .nomor b { color: #18222f; font-size: 11px; }
        .teras { height: 5px; background: #e9ecf0; border-top: 2px solid #cdd3da; margin: 8px 0 16px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data td { padding: 5px 0; vertical-align: top; }
        table.data td.label { width: 34%; color: #5a6573; }
        .jumlah { margin-top: 12px; border: 1.5px solid #2e6a4c; background: #e2efe7; border-radius: 6px; padding: 10px 14px; }
        .jumlah .rp { font-size: 20px; font-weight: bold; color: #2e6a4c; }
        .jumlah .kata { font-style: italic; color: #2e6a4c; }
        .kaki { margin-top: 14px; width: 100%; border-collapse: collapse; font-size: 10px; color: #5a6573; }
        .lunas { display: inline-block; border: 2px solid #2e6a4c; color: #2e6a4c; font-weight: bold; font-size: 13px; padding: 3px 12px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="atap"></div>
    <div class="isi">
        <table class="kepala">
            <tr>
                <td style="width: 36px;"><div class="pintu"></div></td>
                <td>
                    <div class="nama">{{ $pengaturan['nama_kontrakan'] }}</div>
                    <div class="judul">Kuitansi pembayaran sewa</div>
                </td>
                <td class="nomor">Nomor<br><b>{{ $nomor }}</b></td>
            </tr>
        </table>
        <div class="teras"></div>

        <table class="data">
            <tr><td class="label">Diterima dari</td><td><b>{{ $penyewa->name }}</b> ({{ $penyewa->kode_penyewa }})</td></tr>
            <tr><td class="label">Untuk pembayaran</td><td>Sewa {{ Format::bulan($invoice->periode) }}, unit {{ $invoice->lease->unit->kode_unit }}</td></tr>
            @if ($pembayaran)
                <tr><td class="label">Tanggal transfer</td><td>{{ Format::tanggal($pembayaran->tgl_transfer) }}</td></tr>
                <tr><td class="label">Tanggal lunas</td><td>{{ Format::tanggal($pembayaran->reviewed_at) }}</td></tr>
            @endif
        </table>

        <div class="jumlah">
            <div class="rp">{{ Format::rupiah($invoice->total) }}</div>
            <div class="kata">{{ ucfirst(Format::terbilang($invoice->total)) }}</div>
        </div>

        <table class="kaki">
            <tr>
                <td>
                    @if ($pembayaran?->pemeriksa)
                        Disetujui oleh {{ $pembayaran->pemeriksa->name }}.<br>
                    @endif
                    Dibuat otomatis oleh aplikasi {{ $pengaturan['nama_kontrakan'] }}, sah tanpa tanda tangan.
                </td>
                <td style="text-align: right;"><span class="lunas">LUNAS</span></td>
            </tr>
        </table>
    </div>
</body>
</html>
