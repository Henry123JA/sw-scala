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
        if (!Schema::hasTable('metodo_pago')) {
            Schema::create('metodo_pago', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50)->unique();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('especialidad')) {
            Schema::create('especialidad', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100)->unique();
                $table->string('descripcion', 255)->nullable();
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sala')) {
            Schema::create('sala', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('tipo', 50)->nullable();
                $table->integer('capacidad');
                $table->string('ubicacion_piso', 50)->nullable();
                $table->string('equipamiento', 255)->nullable();
                $table->string('estado', 20)->default('ACTIVO');
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('curso')) {
            Schema::create('curso', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 150);
                $table->text('descripcion')->nullable();
                $table->string('tipo_ensenanza', 50)->nullable();
                $table->string('duracion_estandar', 50)->nullable();
                $table->decimal('precio', 10, 2);
                $table->string('estado', 20)->default('ACTIVO');
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('alumno')) {
            Schema::create('alumno', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->foreign('id')->references('id')->on('usuario')->onDelete('restrict');
                $table->string('codigo', 20)->nullable()->unique();
                $table->string('estado', 20)->default('ACTIVO');
                $table->date('fecha_nacimiento')->nullable();
                $table->string('sexo', 10)->nullable();
                $table->string('telefono', 20)->nullable();
                $table->string('telefono_alternativo', 20)->nullable();
                $table->string('nivel', 50)->nullable();
                $table->unsignedBigInteger('referido_por')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('docente')) {
            Schema::create('docente', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->foreign('id')->references('id')->on('usuario')->onDelete('restrict');
                $table->string('codigo', 20)->nullable()->unique();
                $table->string('estado', 20)->default('ACTIVO');
                $table->date('fecha_nacimiento')->nullable();
                $table->date('fecha_incorporacion')->nullable();
                $table->string('telefono', 20)->nullable();
                $table->decimal('tarifa_horaria', 10, 2)->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('especialidad_docente')) {
            Schema::create('especialidad_docente', function (Blueprint $table) {
                $table->id();
                $table->foreignId('docente_id')->constrained('docente')->onDelete('restrict');
                $table->foreignId('especialidad_id')->constrained('especialidad')->onDelete('restrict');
                $table->timestamps();
                $table->unique(['docente_id', 'especialidad_id']);
            });
        }

        if (!Schema::hasTable('horario_docente')) {
            Schema::create('horario_docente', function (Blueprint $table) {
                $table->id();
                $table->foreignId('docente_id')->constrained('docente')->onDelete('restrict');
                $table->string('dia_semana', 10);
                $table->time('hora_inicio');
                $table->time('hora_fin');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('grupo')) {
            Schema::create('grupo', function (Blueprint $table) {
                $table->id();
                $table->string('codigo_grupo', 20)->unique();
                $table->foreignId('curso_id')->constrained('curso')->onDelete('restrict');
                $table->foreignId('docente_id')->constrained('docente')->onDelete('restrict');
                $table->integer('capacidad_maxima');
                $table->string('nivel', 50)->nullable();
                $table->string('estado', 20)->default('ACTIVO');
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('horario_grupo')) {
            Schema::create('horario_grupo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grupo_id')->constrained('grupo')->onDelete('restrict');
                $table->foreignId('sala_id')->constrained('sala')->onDelete('restrict');
                $table->string('dia_semana', 10);
                $table->time('hora_inicio');
                $table->time('hora_fin');
                $table->string('tipo_sesion', 50)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inscripcion')) {
            Schema::create('inscripcion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('alumno_id')->constrained('alumno')->onDelete('restrict');
                $table->foreignId('grupo_id')->constrained('grupo')->onDelete('restrict');
                $table->date('fecha')->useCurrent();
                $table->date('fecha_inicio_clases')->nullable();
                $table->date('fecha_pausa')->nullable();
                $table->date('fecha_retorno')->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->decimal('monto_mensual', 10, 2);
                $table->string('estado', 20)->default('ACTIVA');
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mensualidad')) {
            Schema::create('mensualidad', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inscripcion_id')->constrained('inscripcion')->onDelete('restrict');
                $table->foreignId('metodo_pago_id')->nullable()->constrained('metodo_pago')->onDelete('restrict');
                $table->integer('numero_mes');
                $table->decimal('monto_base', 10, 2);
                $table->date('fecha_vencimiento');
                $table->date('fecha_pago')->nullable();
                $table->string('estado', 20)->default('PENDIENTE');
                $table->string('numero_recibo', 30)->nullable()->unique();
                $table->string('tipo_comprobante', 30)->nullable();
                $table->string('comprobante', 255)->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();
                $table->unique(['inscripcion_id', 'numero_mes']);
            });
        }

        // Add self-referencing foreign key for referred_by on alumno after the table is fully created
        Schema::table('alumno', function (Blueprint $table) {
            $table->foreign('referido_por')->references('id')->on('alumno')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->dropForeign(['referido_por']);
        });

        Schema::dropIfExists('mensualidad');
        Schema::dropIfExists('inscripcion');
        Schema::dropIfExists('horario_grupo');
        Schema::dropIfExists('grupo');
        Schema::dropIfExists('horario_docente');
        Schema::dropIfExists('especialidad_docente');
        Schema::dropIfExists('docente');
        Schema::dropIfExists('alumno');
        Schema::dropIfExists('curso');
        Schema::dropIfExists('sala');
        Schema::dropIfExists('especialidad');
        Schema::dropIfExists('metodo_pago');
    }
};
