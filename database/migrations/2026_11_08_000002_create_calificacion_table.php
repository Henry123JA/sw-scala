<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('calificacion')) {
            Schema::create('calificacion', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('evaluacion_id')->constrained('evaluacion')->cascadeOnDelete();
                $table->foreignId('inscripcion_id')->constrained('inscripcion')->cascadeOnDelete();
                $table->decimal('nota', 5, 2);
                $table->string('observaciones', 255)->nullable();
                $table->timestamps();
                $table->unique(['evaluacion_id', 'inscripcion_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion');
    }
};
