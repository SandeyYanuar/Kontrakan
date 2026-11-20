@props(['warna' => 'text-roof', 'kosong' => false])

{{-- Ikon rumah sebagai penanda unit. Warna lewat kelas teks (text-lunas, text-telat, ...); kosong = garis putus-putus. --}}
<svg viewBox="0 0 64 58" {{ $attributes->merge(['class' => $kosong ? 'text-belum' : $warna, 'aria-hidden' => 'true']) }}>
    @if ($kosong)
        <path d="M32 4 61 28h-6v27H9V28H3L32 4Z" fill="rgb(255 255 255 / 0.55)" stroke="currentColor" stroke-width="2.5" stroke-dasharray="5 4" stroke-linejoin="round"/>
    @else
        <path d="M32 3 62 28h-7v27H9V28H2L32 3Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <path d="M2 28 32 3l30 25" fill="none" stroke="rgb(0 0 0 / 0.18)" stroke-width="3" stroke-linejoin="round"/>
        <rect x="26" y="35" width="12" height="20" rx="1.5" fill="rgb(255 255 255 / 0.32)"/>
        <circle cx="35" cy="45.5" r="1.3" fill="rgb(255 255 255 / 0.85)"/>
        <rect x="14" y="33" width="8" height="8" rx="1" fill="rgb(255 255 255 / 0.28)"/>
        <rect x="42" y="33" width="8" height="8" rx="1" fill="rgb(255 255 255 / 0.28)"/>
    @endif
</svg>
