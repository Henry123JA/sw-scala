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
        Schema::create('intervencion_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerta_id')
                ->constrained('alerta_seguimiento')
                ->cascadeOnDelete();
            $table->foreignId('inscripcion_id')
                ->constrained('inscripcion')
                ->cascadeOnDelete();
            $table->foreignId('usuario_id')
                ->constrained('usuario')
                ->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tipo', 40); // TUTORIA_INDIVIDUAL, REFUERZO_TECNICO, CITACION_PADRES, LLAMADA_SEGUIMIENTO, REPROGRAMACION
            $table->string('titulo', 150);
            $table->text('descripcion');
            $table->text('compromiso')->nullable();
            $table->string('resultado', 30)->default('EN_PROCESO'); // EN_PROCESO, MEJORA_EVIDENTE, SIN_CAMBIOS, REQUIERE_ESCALAMIENTO
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intervencion_seguimiento');
    }
};

