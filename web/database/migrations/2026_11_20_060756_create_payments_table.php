<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bukti transfer yang diupload penyewa. Satu tagihan bisa punya beberapa bukti (misalnya setelah ditolak),
     * tapi hanya satu yang menunggu dicek pada satu waktu.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('user_id')->constrained('users')->comment('Penyewa yang mengupload');
            $table->string('file_path')->comment('Storage privat');
            $table->unsignedInteger('nominal_transfer');
            $table->date('tgl_transfer');
            $table->string('nama_pengirim')->nullable();
            $table->string('catatan')->nullable();
            $table->string('status', 15)->default('menunggu')->index()->comment('menunggu, disetujui, ditolak, dibatalkan');
            $table->string('alasan')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
