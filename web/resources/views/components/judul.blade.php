@props(['keterangan' => null])

{{-- Judul halaman admin, dengan tempat tombol aksi di kanan. --}}
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight">{{ $slot }}</h1>
        @if ($keterangan)
            <p class="mt-1.5 max-w-prose text-sm text-muted">{{ $keterangan }}</p>
        @endif
    </div>
    @isset($aksi)
        <div class="flex flex-wrap gap-2">{{ $aksi }}</div>
    @endisset
</div>
