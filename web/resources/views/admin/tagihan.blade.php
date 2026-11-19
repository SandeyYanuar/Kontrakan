<x-layouts.admin title="Tagihan" aktif="tagihan">
    <h1 class="text-3xl font-extrabold tracking-tight">Tagihan {{ now()->locale('id')->translatedFormat('F Y') }}</h1>
    <p class="mt-1.5 text-sm text-muted">Belum ada unit yang terdaftar.</p>

    <div class="mt-6 rounded-2xl border border-dashed border-[#aeb7c2] bg-paper px-6 py-10 text-center">
        <p class="font-semibold">Deretan pintu akan muncul di sini</p>
        <p class="mt-1 text-sm text-muted">Tambahkan unit dan penyewa dulu, lalu tagihan bulanan dibuat otomatis.</p>
    </div>
</x-layouts.admin>
