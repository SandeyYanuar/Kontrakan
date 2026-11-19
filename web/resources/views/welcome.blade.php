{{-- Halaman sementara Langkah 1: memastikan layout, font, dan warna terpasang. Diganti halaman login di Langkah 2. --}}
<x-layouts.admin title="Kerangka" aktif="tagihan">
    <h1 class="text-3xl font-extrabold tracking-tight">Kerangka aplikasi siap</h1>
    <p class="mt-1.5 max-w-prose text-sm text-muted">
        Font, warna, dan zona waktu sudah terpasang. Hari ini {{ \App\Support\Format::tanggal(now()) }}.
        Halaman ini diganti halaman login di langkah berikutnya.
    </p>

    <div class="mt-6 overflow-hidden rounded-2xl border border-line bg-paper">
        <div class="atap h-5"></div>
        <div class="flex gap-6 overflow-x-auto px-6 pt-6">
            @foreach (['lunas' => 'Lunas', 'dicek' => 'Perlu dicek', 'dekat' => 'Jatuh tempo', 'telat' => 'Telat'] as $warna => $label)
                <div class="flex flex-col items-center">
                    <div @class([
                        'h-24 w-14 rounded-t',
                        'bg-lunas' => $warna === 'lunas',
                        'bg-dicek' => $warna === 'dicek',
                        'bg-dekat' => $warna === 'dekat',
                        'bg-telat' => $warna === 'telat',
                    ])></div>
                    <p class="mt-2 text-xs font-semibold text-muted">{{ $label }}</p>
                </div>
            @endforeach
        </div>
        <div class="teras mt-3 h-3.5"></div>
    </div>

    <p class="mt-6 text-sm">Contoh nominal: <b>{{ \App\Support\Format::rupiah(4700000) }}</b></p>
</x-layouts.admin>
