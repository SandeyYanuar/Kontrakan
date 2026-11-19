<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto kerusakan, disimpan di storage privat.
     */
    public function up(): void
    {
        Schema::create('ticket_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('jenis', 10)->default('foto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_media');
    }
};
