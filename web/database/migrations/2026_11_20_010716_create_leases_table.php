<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kontrak sewa: siapa menempati unit mana sejak kapan. Pindah unit membuat kontrak baru.
     * Harga sewa tidak disalin ke sini; tagihan memakai harga unit saat tagihan dibuat.
     */
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('unit_id')->constrained('units');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->string('status', 10)->default('aktif')->index()->comment('aktif atau selesai');
            $table->timestamps();

            $table->index(['unit_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
