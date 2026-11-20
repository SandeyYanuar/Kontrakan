@props(['title' => null])

{{-- Layout penyewa: HP lebih dulu, panel kaca di atas foto rumah, dibatasi selebar layar HP di laptop. --}}
<x-layouts.app :title="$title" class="bg-[#cfc6ba]">
    <x-latar-rumah />

    <div class="mx-auto max-w-md p-2">
        <div class="kaca panel-kaca min-h-[calc(100vh-1rem)] overflow-hidden rounded-[22px] pb-6">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
