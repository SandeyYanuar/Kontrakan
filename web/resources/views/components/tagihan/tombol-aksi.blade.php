@props(['baris', 'periode', 'lebar' => false])

{{-- Tindakan untuk satu penyewa: ingatkan lewat WhatsApp, cek bukti transfer, atau centang lunas. --}}
@if ($baris->kelompok() === \App\Support\BarisTagihan::KELOMPOK_LUNAS)
    <svg viewBox="0 0 24 24" class="size-[22px]" role="img" aria-label="Lunas">
        <circle cx="12" cy="12" r="11" fill="#e2efe7"/>
        <path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#2e6a4c" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@else
    <div @class(['flex gap-2', 'flex-col' => $lebar])>
        @if ($baris->menungguDicek()->isNotEmpty())
            <button type="button" data-cek-bukti="{{ $baris->menungguDicek()->first()->id }}"
                @class(['inline-flex items-center justify-center whitespace-nowrap rounded-[9px] bg-dicek px-3.5 py-2.5 text-[13px] font-semibold text-white hover:bg-[#244274]', 'w-full py-3' => $lebar])>
                Cek bukti transfer
            </button>
        @endif

        @if ($baris->harusDibayar()->isNotEmpty())
            <form method="POST" action="{{ route('admin.tagihan.ingatkan', $baris->lease) }}" target="_blank">
                @csrf
                <input type="hidden" name="bulan" value="{{ $periode }}">
                <button type="submit"
                    @class(['inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[9px] bg-wa px-3.5 py-2.5 text-[13px] font-semibold text-white hover:bg-wa-hover', 'w-full py-3' => $lebar])>
                    <svg viewBox="0 0 24 24" class="size-4 fill-white" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-3.2-.7-2.7-1.1-4.4-3.9-4.6-4.1-.1-.2-1.1-1.4-1.1-2.7 0-1.3.7-1.9.9-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.3.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 1.9 1.1.9 1.9 1.2 2.2 1.3.3.1.4.1.6-.1l.8-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z"/></svg>
                    Ingatkan via WhatsApp
                </button>
            </form>
        @endif
    </div>
@endif
