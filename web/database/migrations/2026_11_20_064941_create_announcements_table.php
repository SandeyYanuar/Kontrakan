<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengumuman dari pengelola yang tampil di beranda penyewa (Tahap 2).
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 120);
            $table->text('isi');
            $table->date('tampil_mulai');
            $table->date('tampil_sampai')->nullable()->comment('Kosong berarti tampil sampai diakhiri');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
