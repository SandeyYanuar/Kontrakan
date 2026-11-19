@props(['jenis' => 'utama', 'href' => null])

@php
    $kelas = match ($jenis) {
        'utama' => 'bg-dicek text-white hover:bg-[#244274]',
        'sekunder' => 'border border-line bg-paper text-ink hover:bg-soft',
        'bahaya' => 'border border-telat bg-paper text-telat hover:bg-telat-bg',
        'tenang' => 'text-dicek hover:bg-dicek-bg',
    };
    $dasar = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[9px] px-3.5 py-2.5 text-sm font-semibold';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$dasar, $kelas]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class([$dasar, $kelas]) }}>{{ $slot }}</button>
@endif
