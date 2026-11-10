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
        Schema::create('version_modelo', function (Blueprint $table) {
            $table->id();
            $table->string('version', 30)->unique();
            $table->string('nombre', 120);
            $table->string('algoritmo', 80);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->decimal('f1_score', 5, 2)->default(0);
            $table->date('fecha_entrenamiento');
            $table->string('url_endpoint', 255)->nullable();
            $table->boolean('activo')->default(false);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_modelo');
    }
};

