<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensualidad', function (Blueprint $table) {
            if (!Schema::hasColumn('mensualidad', 'meses_pagados')) {
                $table->integer('meses_pagados')->default(1)->after('numero_mes');
            }
        });

        // Hacer fecha_vencimiento nullable en mensualidad (ya no se usa, se usa la de inscripcion)
        Schema::table('mensualidad', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->change();
        });

        // Asegurar que inscripcion tenga fecha_vencimiento no nula
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable(false)->default(now()->toDateString())->change();
        });
    }

    public function down(): void
    {
        Schema::table('mensualidad', function (Blueprint $table) {
            if (Schema::hasColumn('mensualidad', 'meses_pagados')) {
                $table->dropColumn('meses_pagados');
            }
        });

        Schema::table('mensualidad', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable(false)->change();
        });

        Schema::table('inscripcion', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->default(null)->change();
        });
    }
};
