<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data KTP penyewa. NIK disimpan terenkripsi, foto di storage privat.
     */
    public function up(): void
    {
        Schema::create('tenant_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->text('nik')->nullable()->comment('Terenkripsi');
            $table->string('nama_ktp')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('status', 15)->default('belum_ada')->comment('belum_ada, menunggu, sah, ditolak');
            $table->string('alasan')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_identities');
    }
};
