@props(['title' => null])

{{-- Layout penyewa: dirancang untuk HP lebih dulu, dibatasi selebar layar HP di laptop. --}}
<x-layouts.app :title="$title" class="bg-plaster">
    <div class="mx-auto min-h-screen max-w-md bg-plaster pb-10 sm:border-x sm:border-line">
        {{ $slot }}
    </div>
</x-layouts.app>
