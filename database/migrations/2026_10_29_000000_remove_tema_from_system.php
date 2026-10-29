<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Desacopla y elimina la tabla 'tema' y la columna 'tema_id' de 'usuario'.
     */
    public function up(): void
    {
        if (Schema::hasTable('usuario') && Schema::hasColumn('usuario', 'tema_id')) {
            Schema::table('usuario', function (Blueprint $table) {
                // Se elimina la clave foránea y luego la columna
                $table->dropForeign(['tema_id']);
                $table->dropColumn('tema_id');
            });
        }

        if (Schema::hasTable('tema')) {
            Schema::dropIfExists('tema');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tema')) {
            Schema::create('tema', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50)->unique();
                $table->string('font_size', 10)->default('medium');
                $table->string('descripcion', 255)->nullable();
                $table->string('color_primario_dia', 20)->nullable();
                $table->string('color_secundario_dia', 20)->nullable();
                $table->string('contraste_dia', 20)->nullable();
                $table->string('color_primario_noche', 20)->nullable();
                $table->string('color_secundario_noche', 20)->nullable();
                $table->string('contraste_noche', 20)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('usuario') && !Schema::hasColumn('usuario', 'tema_id')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->foreignId('tema_id')->nullable()->constrained('tema')->nullOnDelete();
            });
        }
    }
};

