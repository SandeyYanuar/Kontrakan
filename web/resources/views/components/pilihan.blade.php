@props(['label', 'name', 'value' => null, 'hint' => null])

{{-- Isian pilihan (select). Isi <option> lewat slot. --}}
<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($hint || $errors->has($name)) aria-describedby="{{ $name }}-keterangan" @endif
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->class([
            'w-full rounded-[10px] border bg-paper px-3 py-2.5 text-[15px] focus:outline-3 focus:outline-dicek/30',
            'border-telat' => $errors->has($name),
            'border-[#cdd3da] focus:border-dicek' => ! $errors->has($name),
        ]) }}
    >
        {{ $slot }}
    </select>
    <div id="{{ $name }}-keterangan">
        @error($name)
            <p class="mt-1.5 text-sm font-medium text-telat" role="alert">{{ $message }}</p>
        @else
            @if ($hint)
                <p class="mt-1.5 text-[13px] text-muted">{{ $hint }}</p>
            @endif
        @enderror
    </div>
</div>
