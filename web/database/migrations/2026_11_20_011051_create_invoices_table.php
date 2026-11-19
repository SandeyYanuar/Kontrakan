<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan sewa bulanan. Satu kontrak sewa punya paling banyak satu tagihan per periode.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained('leases');
            $table->string('periode', 7)->comment('YYYY-MM');
            $table->date('jatuh_tempo');
            $table->unsignedInteger('total');
            $table->string('status', 15)->default('belum_bayar')->index();
            $table->timestamps();

            $table->unique(['lease_id', 'periode']);
            $table->index('periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
