<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asistencia')) {
            Schema::create('asistencia', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('sesion_clase_id')->constrained('sesion_clase')->cascadeOnDelete();
                $table->foreignId('inscripcion_id')->constrained('inscripcion')->cascadeOnDelete();
                $table->string('estado');
                $table->text('observacion')->nullable();
                $table->timestamps();
                $table->unique(['sesion_clase_id', 'inscripcion_id']);
            });
            DB::statement("ALTER TABLE asistencia ADD CONSTRAINT asistencia_estado_check CHECK (estado IN ('PRESENTE', 'FALTA', 'LICENCIA', 'ATRASO'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia');
    }
};
