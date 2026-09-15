<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensualidad', function (Blueprint $table) {
            // La unique constraint (inscripcion_id, numero_mes) ya no es necesaria.
            // Ahora cada pago crea un solo registro con recibo único.
            $table->dropUnique('mensualidad_inscripcion_id_numero_mes_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mensualidad', function (Blueprint $table) {
            $table->unique(['inscripcion_id', 'numero_mes']);
        });
    }
};
