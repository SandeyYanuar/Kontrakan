<?php

namespace App\Providers;

use App\Enums\StatusLaporan;
use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('super-admin', fn (User $user): bool => $user->isSuperAdmin());

        // Angka di menu admin: tagihan yang perlu ditindak dan laporan kerusakan yang belum selesai.
        View::composer('components.layouts.admin', function ($view): void {
            $hariIni = now()->toDateString();

            $view->with('jumlahMenu', [
                'tagihan' => Invoice::query()
                    ->whereHas('lease', fn ($q) => $q->where('status', Lease::AKTIF))
                    ->where(fn ($q) => $q->where('status', StatusTagihan::Menunggu)
                        ->orWhere(fn ($q) => $q->whereIn('status', [StatusTagihan::BelumBayar, StatusTagihan::Ditolak])->whereDate('jatuh_tempo', '<', $hariIni)))
                    ->distinct()
                    ->count('lease_id'),
                'laporan' => MaintenanceTicket::query()->where('status', '!=', StatusLaporan::Selesai)->count(),
            ]);
        });
    }
}
