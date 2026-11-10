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
        Schema::create('alerta_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')
                ->constrained('inscripcion')
                ->cascadeOnDelete();
            $table->foreignId('version_modelo_id')
                ->nullable()
                ->constrained('version_modelo')
                ->nullOnDelete();
            $table->string('nivel_riesgo', 20)->index(); // ALTO, MEDIO, BAJO
            $table->decimal('score_riesgo', 5, 2)->default(0);
            $table->json('factores_riesgo');
            $table->json('metricas_base')->nullable();
            $table->text('resumen_explicativo')->nullable();
            $table->string('estado', 25)->default('PENDIENTE')->index(); // PENDIENTE, EN_ATENCION, RESUELTA, DESCARTADA
            $table->string('origen', 30)->default('MOTOR_LOCAL'); // ML_API, N8N_AUTO, MOTOR_LOCAL
            $table->timestamp('fecha_analisis')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerta_seguimiento');
    }
};

