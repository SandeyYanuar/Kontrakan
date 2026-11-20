<x-layouts.app title="Masuk">
    <main class="mx-auto flex min-h-screen max-w-md flex-col px-4 py-8 sm:justify-center">
        {{-- Deretan rumah kecil: motif utama aplikasi. --}}
        <div class="overflow-hidden rounded-t-2xl border border-b-0 border-line" aria-hidden="true">
            <div class="atap h-4"></div>
            <div class="flex items-end justify-center gap-2 bg-paper px-6 pt-5 pb-1">
                @foreach (['text-lunas', 'text-dicek', 'text-dekat', 'text-lunas', 'text-telat'] as $warna)
                    <x-rumah :warna="$warna" class="h-12 w-14 drop-shadow-sm" />
                @endforeach
            </div>
            <div class="teras h-2.5"></div>
        </div>

        <div class="rounded-b-2xl border border-t-0 border-line bg-paper px-6 pt-6 pb-7">
            <h1 class="text-2xl font-extrabold tracking-tight">{{ \App\Models\Pengaturan::ambil('nama_kontrakan') }}</h1>
            <p class="mt-1 text-sm text-muted">Masuk dengan nomor WhatsApp kamu.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4" novalidate>
                @csrf

                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-semibold">Nomor HP</label>
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        value="{{ old('phone') }}"
                        placeholder="0812 3456 7890"
                        required
                        autofocus
                        @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                        @class([
                            'w-full rounded-[10px] border bg-paper px-3 py-2.5 text-[15px] focus:outline-3 focus:outline-dicek/30',
                            'border-telat' => $errors->has('phone'),
                            'border-[#cdd3da] focus:border-dicek' => ! $errors->has('phone'),
                        ])
                    >
                    @error('phone')
                        <p id="phone-error" class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        class="w-full rounded-[10px] border border-[#cdd3da] bg-paper px-3 py-2.5 text-[15px] focus:border-dicek focus:outline-3 focus:outline-dicek/30"
                    >
                    @error('password')
                        <p id="password-error" class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2.5 text-sm">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded accent-dicek">
                    Ingat saya selama 30 hari
                </label>

                <button type="submit" class="w-full rounded-xl bg-dicek px-4 py-3 text-[15px] font-bold text-white hover:bg-[#244274]">
                    Masuk
                </button>
            </form>

            @if ($linkLupaPassword)
                <p class="mt-5 text-center text-sm text-muted">
                    Lupa password?
                    <a href="{{ $linkLupaPassword }}" target="_blank" rel="noopener" class="font-semibold text-dicek underline-offset-2 hover:underline">Minta reset lewat WhatsApp</a>
                </p>
            @endif
        </div>
    </main>
</x-layouts.app>
