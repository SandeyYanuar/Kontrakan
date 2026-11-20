@props(['unit' => null, 'ukuran' => 'h-[42px] w-12'])

{{-- Logo unit: ikon rumah dengan warna khas setiap kode unit, kode unit di badan rumah. Tanpa unit = rumah garis putus-putus. --}}
<span {{ $attributes->class(['relative inline-block shrink-0', $ukuran]) }}>
    @if ($unit)
        <x-rumah warna="" polos :style="'color: '.$unit->warnaLogo()" class="size-full drop-shadow-sm" />
        <span class="absolute inset-x-0 bottom-[16%] text-center text-[12px] leading-none font-extrabold tracking-wide text-white">{{ $unit->kode_unit }}</span>
    @else
        <x-rumah kosong class="size-full" />
    @endif
</span>
