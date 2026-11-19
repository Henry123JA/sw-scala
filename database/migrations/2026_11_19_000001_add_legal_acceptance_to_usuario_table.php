<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            if (! Schema::hasColumn('usuario', 'politica_aceptada_at')) {
                $table->timestamp('politica_aceptada_at')->nullable()->after('foto');
            }
            if (! Schema::hasColumn('usuario', 'terminos_aceptados_at')) {
                $table->timestamp('terminos_aceptados_at')->nullable()->after('politica_aceptada_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('usuario', 'politica_aceptada_at')) {
                $columns[] = 'politica_aceptada_at';
            }
            if (Schema::hasColumn('usuario', 'terminos_aceptados_at')) {
                $columns[] = 'terminos_aceptados_at';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
