<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Support\Aktivitas;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        return view('admin.unit.index', ['units' => Unit::query()->orderBy('kode_unit')->get()]);
    }

    public function create(): View
    {
        return view('admin.unit.form', ['unit' => new Unit]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = Unit::create($this->validasi($request));

        Aktivitas::catat(Aksi::UnitDibuat, $unit, ['kode_unit' => $unit->kode_unit, 'harga_sewa' => $unit->harga_sewa]);

        return redirect()->route('admin.unit.index')->with('status', "Unit {$unit->kode_unit} ditambahkan.");
    }

    public function edit(Unit $unit): View
    {
        return view('admin.unit.form', ['unit' => $unit]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $hargaLama = $unit->harga_sewa;
        $unit->fill($this->validasi($request, $unit));

        if (! $unit->isDirty()) {
            return redirect()->route('admin.unit.index');
        }

        $unit->save();

        if ($unit->wasChanged('harga_sewa')) {
            Aktivitas::catat(Aksi::HargaUnitDiubah, $unit, [
                'kode_unit' => $unit->kode_unit,
                'harga_lama' => $hargaLama,
                'harga_baru' => $unit->harga_sewa,
            ]);
        } else {
            Aktivitas::catat(Aksi::UnitDiubah, $unit, ['kode_unit' => $unit->kode_unit, 'perubahan' => array_keys($unit->getChanges())]);
        }

        return redirect()->route('admin.unit.index')->with('status', "Unit {$unit->kode_unit} disimpan.");
    }

    /**
     * @return array{kode_unit: string, harga_sewa: int, keterangan: ?string}
     */
    private function validasi(Request $request, ?Unit $unit = null): array
    {
        $request->merge([
            'kode_unit' => strtoupper(trim((string) $request->input('kode_unit'))),
            'harga_sewa' => Format::angka($request->input('harga_sewa')),
        ]);

        $data = $request->validate([
            'kode_unit' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9\-]+$/', Rule::unique('units')->ignore($unit)],
            'harga_sewa' => ['required', 'integer', 'min:1', 'max:100000000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kode_unit.required' => 'Kode unit wajib diisi.',
            'kode_unit.unique' => 'Kode unit ini sudah dipakai.',
            'kode_unit.regex' => 'Kode unit hanya boleh huruf, angka, atau strip. Contoh: A1.',
            'harga_sewa.required' => 'Harga sewa wajib diisi.',
            'harga_sewa.min' => 'Harga sewa wajib diisi.',
        ]);

        $data['harga_sewa'] = (int) $data['harga_sewa'];
        $data['keterangan'] ??= null;

        return $data;
    }
}
