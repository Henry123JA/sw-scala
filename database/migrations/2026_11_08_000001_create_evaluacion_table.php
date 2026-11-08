<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('evaluacion')) {
            Schema::create('evaluacion', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('grupo_id')->constrained('grupo')->cascadeOnDelete();
                $table->string('nombre', 100);
                $table->string('tipo', 50);
                $table->date('fecha');
                $table->decimal('ponderacion', 5, 2)->default(100.00);
                $table->string('descripcion', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion');
    }
};
