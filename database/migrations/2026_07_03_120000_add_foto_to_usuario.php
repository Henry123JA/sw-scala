<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add nullable `foto` column to the `usuario` table.
     * Stores the relative path under storage/app/public/, e.g.
     *   usuarios/fotos/42/abc-uuid.jpg
     */
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->string('foto', 255)->nullable()->after('tema_id');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
