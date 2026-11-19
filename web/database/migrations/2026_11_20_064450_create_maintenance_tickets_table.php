<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laporan kerusakan dari penyewa (Tahap 2).
     */
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained('leases');
            $table->string('kategori', 20);
            $table->text('deskripsi');
            $table->date('preferensi_tanggal')->nullable();
            $table->string('preferensi_waktu', 10)->nullable()->comment('pagi, siang, sore');
            $table->dateTime('jadwal_kunjungan')->nullable();
            $table->string('status', 15)->default('diterima')->index()->comment('diterima, dijadwalkan, dikerjakan, selesai');
            $table->timestamp('selesai_at')->nullable();
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
