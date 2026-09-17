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
        // 1. Eliminar tablas financieras
        Schema::dropIfExists('mensualidad');
        Schema::dropIfExists('metodo_pago');

        // 2. Modificar tabla curso: eliminar precio
        if (Schema::hasTable('curso') && Schema::hasColumn('curso', 'precio')) {
            Schema::table('curso', function (Blueprint $table) {
                $table->dropColumn('precio');
            });
        }

        // 3. Modificar tabla docente: eliminar tarifa_horaria
        if (Schema::hasTable('docente') && Schema::hasColumn('docente', 'tarifa_horaria')) {
            Schema::table('docente', function (Blueprint $table) {
                $table->dropColumn('tarifa_horaria');
            });
        }

        // 4. Modificar tabla inscripcion:
        //    - Agregar fecha_retiro
        //    - Actualizar registros con estado 'VENCIDA' a 'CANCELADA' antes de agregar la restricción
        //    - Agregar restricción CHECK para estado: ACTIVA, PAUSADA, CANCELADA
        //    - Eliminar monto_mensual y fecha_vencimiento
        if (Schema::hasTable('inscripcion')) {
            if (!Schema::hasColumn('inscripcion', 'fecha_retiro')) {
                Schema::table('inscripcion', function (Blueprint $table) {
                    $table->date('fecha_retiro')->nullable()->after('fecha_retorno');
                });
            }

            // Migrar registros existentes con estado VENCIDA a CANCELADA
            DB::statement("
                UPDATE inscripcion 
                SET estado = 'CANCELADA', 
                    fecha_retiro = COALESCE(fecha_vencimiento, CURRENT_DATE) 
                WHERE estado = 'VENCIDA'
            ");

            // Eliminar restricción previa si existiese
            DB::statement("ALTER TABLE inscripcion DROP CONSTRAINT IF EXISTS chk_inscripcion_estado");

            // Establecer restricción CHECK estricta: únicamente ACTIVA, PAUSADA, CANCELADA
            DB::statement("
                ALTER TABLE inscripcion 
                ADD CONSTRAINT chk_inscripcion_estado 
                CHECK (estado IN ('ACTIVA', 'PAUSADA', 'CANCELADA'))
            ");

            Schema::table('inscripcion', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('inscripcion', 'monto_mensual')) {
                    $columnsToDrop[] = 'monto_mensual';
                }
                if (Schema::hasColumn('inscripcion', 'fecha_vencimiento')) {
                    $columnsToDrop[] = 'fecha_vencimiento';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inscripcion')) {
            DB::statement("ALTER TABLE inscripcion DROP CONSTRAINT IF EXISTS chk_inscripcion_estado");

            Schema::table('inscripcion', function (Blueprint $table) {
                if (!Schema::hasColumn('inscripcion', 'monto_mensual')) {
                    $table->decimal('monto_mensual', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('inscripcion', 'fecha_vencimiento')) {
                    $table->date('fecha_vencimiento')->nullable();
                }
                if (Schema::hasColumn('inscripcion', 'fecha_retiro')) {
                    $table->dropColumn('fecha_retiro');
                }
            });
        }

        if (Schema::hasTable('docente') && !Schema::hasColumn('docente', 'tarifa_horaria')) {
            Schema::table('docente', function (Blueprint $table) {
                $table->decimal('tarifa_horaria', 10, 2)->nullable();
            });
        }

        if (Schema::hasTable('curso') && !Schema::hasColumn('curso', 'precio')) {
            Schema::table('curso', function (Blueprint $table) {
                $table->decimal('precio', 10, 2)->default(0);
            });
        }

        if (!Schema::hasTable('metodo_pago')) {
            Schema::create('metodo_pago', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50)->unique();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mensualidad')) {
            Schema::create('mensualidad', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inscripcion_id')->constrained('inscripcion')->onDelete('restrict');
                $table->foreignId('metodo_pago_id')->nullable()->constrained('metodo_pago')->onDelete('restrict');
                $table->integer('numero_mes');
                $table->integer('meses_pagados')->default(1);
                $table->decimal('monto_base', 10, 2);
                $table->date('fecha_vencimiento')->nullable();
                $table->date('fecha_pago')->nullable();
                $table->string('estado', 20)->default('PENDIENTE');
                $table->string('numero_recibo', 30)->nullable();
                $table->string('tipo_comprobante', 30)->nullable();
                $table->string('comprobante', 255)->nullable();
                $table->text('observaciones')->nullable();
                $table->string('pagofacil_transaction_id')->nullable();
                $table->string('pagofacil_payment_number')->nullable()->index();
                $table->text('pagofacil_qr_base64')->nullable();
                $table->timestamp('pagofacil_qr_expira')->nullable();
                $table->timestamps();
            });
        }
    }
};
