<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat aktivitas hanya boleh ditambah. Selain dijaga di model, database menolak
     * UPDATE dan DELETE lewat trigger supaya catatan tidak bisa diubah dari jalur mana pun.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->comment('Null jika dilakukan sistem');
            $table->string('aksi', 50)->index();
            $table->nullableMorphs('subject');
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        match (DB::getDriverName()) {
            'sqlite' => $this->triggerSqlite(),
            'mysql', 'mariadb' => $this->triggerMysql(),
            default => null,
        };
    }

    private function triggerSqlite(): void
    {
        DB::unprepared("CREATE TRIGGER activity_logs_tolak_update BEFORE UPDATE ON activity_logs BEGIN SELECT RAISE(ABORT, 'Riwayat aktivitas tidak bisa diubah'); END;");
        DB::unprepared("CREATE TRIGGER activity_logs_tolak_delete BEFORE DELETE ON activity_logs BEGIN SELECT RAISE(ABORT, 'Riwayat aktivitas tidak bisa dihapus'); END;");
    }

    private function triggerMysql(): void
    {
        DB::unprepared("CREATE TRIGGER activity_logs_tolak_update BEFORE UPDATE ON activity_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Riwayat aktivitas tidak bisa diubah'");
        DB::unprepared("CREATE TRIGGER activity_logs_tolak_delete BEFORE DELETE ON activity_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Riwayat aktivitas tidak bisa dihapus'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS activity_logs_tolak_update');
        DB::unprepared('DROP TRIGGER IF EXISTS activity_logs_tolak_delete');
        Schema::dropIfExists('activity_logs');
    }
};
