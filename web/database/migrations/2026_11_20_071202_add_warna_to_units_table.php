<?php

use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Warna khas setiap unit untuk logo rumahnya. Unit yang sudah ada diberi warna berurutan.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('warna', 7)->nullable()->after('keterangan');
        });

        $kode = DB::table('units')->orderBy('kode_unit')->pluck('kode_unit', 'id');
        $i = 0;
        foreach ($kode as $id => $k) {
            DB::table('units')->where('id', $id)->update(['warna' => Unit::PALET[$i++ % count(Unit::PALET)]]);
        }
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('warna');
        });
    }
};
