<?php

use App\Enums\Aksi;
use App\Enums\StatusLaporan;
use App\Enums\StatusTagihan;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\MaintenanceTicket;
use App\Models\Pengaturan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));
});

test('dashboard admin memakai latar foto rumah bawaan dan bilah samping', function () {
    $this->actingAs(User::factory()->superAdmin()->create(['name' => 'Pangeran']))
        ->get(route('admin.tagihan'))
        ->assertOk()
        ->assertSee('img/latar-rumah.jpg', false)
        ->assertSee('aria-label="Menu admin"', false)
        ->assertSeeInOrder(['Harian', 'Tagihan', 'Penyewa', 'Kerusakan', 'Pengumuman', 'Kelola', 'Unit', 'Riwayat', 'Admin', 'Pengaturan'])
        ->assertSee('aria-label="Menu bawah"', false);
});

test('angka di menu menunjukkan tagihan yang perlu ditindak dan laporan yang belum selesai', function () {
    $telat = Lease::factory()->create();
    Invoice::factory()->for($telat)->create(['periode' => '2026-11', 'jatuh_tempo' => '2026-11-05']);
    $dicek = Lease::factory()->create();
    Invoice::factory()->for($dicek)->create(['periode' => '2026-11', 'jatuh_tempo' => '2026-11-05', 'status' => StatusTagihan::Menunggu]);
    $lunas = Lease::factory()->create();
    Invoice::factory()->for($lunas)->create(['periode' => '2026-11', 'jatuh_tempo' => '2026-11-05', 'status' => StatusTagihan::Lunas]);

    MaintenanceTicket::factory()->count(2)->create();
    MaintenanceTicket::factory()->create(['status' => StatusLaporan::Selesai]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.penyewa.index'))
        ->assertSee('aria-label="2 tagihan perlu ditindak"', false)
        ->assertSee('aria-label="2 laporan belum selesai"', false);
});

test('admin biasa tidak melihat menu khusus super admin, termasuk di menu HP', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.tagihan'))
        ->assertDontSee(route('admin.pengaturan'), false)
        ->assertDontSee(route('admin.admin.index'), false);
});

test('super admin mengganti dan mengembalikan foto latar', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $data = [
        'nama_kontrakan' => 'Kontrakan', 'tanggal_mulai_bayar' => '27', 'tanggal_jatuh_tempo' => '5',
        'latar' => UploadedFile::fake()->image('rumah.jpg', 1600, 1000)->size(900),
    ];

    $this->actingAs($superAdmin)->put(route('admin.pengaturan.update'), $data)->assertSessionHasNoErrors();

    $path = Pengaturan::ambil('latar_dashboard');
    expect($path)->toStartWith('latar/');
    Storage::disk('local')->assertExists($path);
    expect(ActivityLog::where('aksi', Aksi::PengaturanDiubah)->latest('id')->first()->data)->toHaveKey('latar_dashboard');

    $this->actingAs($superAdmin)->get(route('admin.tagihan'))->assertSee(route('latar'), false);
    $this->actingAs($superAdmin)->get(route('admin.latar'))->assertOk();

    unset($data['latar']);
    $this->actingAs($superAdmin)->put(route('admin.pengaturan.update'), [...$data, 'latar_bawaan' => '1']);

    expect(Pengaturan::ambil('latar_dashboard'))->toBeNull();
    Storage::disk('local')->assertMissing($path);
    $this->actingAs($superAdmin)->get(route('admin.tagihan'))->assertSee('img/latar-rumah.jpg', false);
});

test('foto latar harus gambar', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->from(route('admin.pengaturan'))
        ->put(route('admin.pengaturan.update'), [
            'nama_kontrakan' => 'Kontrakan', 'tanggal_mulai_bayar' => '27', 'tanggal_jatuh_tempo' => '5',
            'latar' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('latar');
});

test('tombol keluar selalu terlihat di bilah atas HP admin dan di kepala beranda penyewa', function () {
    $html = $this->actingAs(User::factory()->admin()->create())->get(route('admin.tagihan'))->getContent();
    $bilahAtas = substr($html, strpos($html, '<header'), strpos($html, '</header>') - strpos($html, '<header'));
    expect($bilahAtas)->toContain(route('logout'))->toContain('Keluar');

    $penyewa = Lease::factory()->create()->penyewa;
    $html = $this->actingAs($penyewa)->get(route('penyewa.beranda'))->getContent();
    $kepala = substr($html, strpos($html, '<header'), strpos($html, '</header>') - strpos($html, '<header'));
    expect($kepala)->toContain(route('logout'))->toContain('Keluar');
});

test('sisi penyewa dan halaman login juga memakai latar foto rumah dan panel kaca', function () {
    $this->get(route('login'))->assertSee('img/latar-rumah.jpg', false)->assertSee('kaca', false);

    $penyewa = Lease::factory()->create()->penyewa;
    $this->actingAs($penyewa)->get(route('penyewa.beranda'))->assertSee('img/latar-rumah.jpg', false)->assertSee('panel-kaca', false);
    $this->actingAs($penyewa)->get(route('latar'))->assertRedirect(asset('img/latar-rumah.jpg'));
});
