<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('observacion_academica')) {
            Schema::create('observacion_academica', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inscripcion_id')->constrained('inscripcion')->cascadeOnDelete();
                $table->foreignId('docente_id')->nullable()->constrained('docente')->nullOnDelete();
                $table->date('fecha');
                $table->string('tipo', 50);
                $table->string('titulo', 150);
                $table->text('descripcion');
                $table->boolean('visible_para_alumno')->default(true);
                $table->timestamps();
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE observacion_academica ADD CONSTRAINT observacion_academica_tipo_check CHECK (tipo IN ('PROGRESO_TECNICO', 'RECOMENDACION', 'DISCIPLINA', 'FELICITACION'))");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observacion_academica');
    }
};
