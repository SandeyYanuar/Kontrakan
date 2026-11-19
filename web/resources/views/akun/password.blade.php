@php($layout = auth()->user()->isPenyewa() ? 'layouts.penyewa' : 'layouts.admin')

<x-dynamic-component :component="$layout" title="Ganti password">
    <div @class(['p-4' => auth()->user()->isPenyewa()])>
        @if (auth()->user()->isPenyewa())
            @session('status')
                <p class="mb-5 rounded-[10px] border border-lunas/30 bg-lunas-bg px-4 py-3 text-sm font-semibold text-lunas" role="status">{{ $value }}</p>
            @endsession
            <a href="{{ route('penyewa.beranda') }}" class="text-sm text-muted">Kembali</a>
        @endif

        <x-judul>Ganti password</x-judul>

        <form method="POST" action="{{ route('akun.password.update') }}" class="max-w-xl space-y-4 rounded-2xl border border-line bg-paper p-6" novalidate>
            @csrf
            @method('PUT')

            <x-field label="Password lama" name="password_lama" type="password" autocomplete="current-password" required />
            <x-field label="Password baru" name="password" type="password" autocomplete="new-password" hint="Minimal 8 karakter." required />
            <x-field label="Ulangi password baru" name="password_confirmation" type="password" autocomplete="new-password" required />

            <div class="pt-2">
                <x-tombol>Ganti password</x-tombol>
            </div>
        </form>
    </div>
</x-dynamic-component>
