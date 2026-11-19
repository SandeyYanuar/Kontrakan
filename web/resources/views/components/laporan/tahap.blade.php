@props(['laporan'])

{{-- Empat tahap perbaikan. Tahap yang sudah lewat berwarna, tahap sekarang ditebalkan. --}}
<ol class="grid grid-cols-4 gap-1.5" aria-label="Tahap perbaikan">
    @foreach (\App\Enums\StatusLaporan::cases() as $tahap)
        @php($lewat = $tahap->urutan() <= $laporan->status->urutan())
        <li @if ($tahap === $laporan->status) aria-current="step" @endif>
            <span @class(['block h-1.5 rounded-full', 'bg-lunas' => $lewat, 'bg-line' => ! $lewat])></span>
            <span @class([
                'mt-1.5 block text-[11px] leading-tight',
                'font-bold text-ink' => $tahap === $laporan->status,
                'text-muted' => $tahap !== $laporan->status,
            ])>{{ $tahap->label() }}</span>
        </li>
    @endforeach
</ol>
