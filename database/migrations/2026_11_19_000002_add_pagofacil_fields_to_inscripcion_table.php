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
        Schema::table('inscripcion', function (Blueprint $table) {
            if (!Schema::hasColumn('inscripcion', 'monto_inscripcion')) {
                $table->decimal('monto_inscripcion', 10, 2)->default(150.00)->after('estado');
            }
            if (!Schema::hasColumn('inscripcion', 'estado_pago')) {
                $table->string('estado_pago', 30)->default('PENDIENTE')->after('monto_inscripcion');
            }
            if (!Schema::hasColumn('inscripcion', 'metodo_pago')) {
                $table->string('metodo_pago', 30)->nullable()->after('estado_pago');
            }
            if (!Schema::hasColumn('inscripcion', 'fecha_pago')) {
                $table->timestamp('fecha_pago')->nullable()->after('metodo_pago');
            }
            if (!Schema::hasColumn('inscripcion', 'numero_recibo')) {
                $table->string('numero_recibo', 50)->nullable()->after('fecha_pago');
            }
            if (!Schema::hasColumn('inscripcion', 'pagofacil_transaction_id')) {
                $table->string('pagofacil_transaction_id', 100)->nullable()->after('numero_recibo');
            }
            if (!Schema::hasColumn('inscripcion', 'pagofacil_payment_number')) {
                $table->string('pagofacil_payment_number', 100)->nullable()->index()->after('pagofacil_transaction_id');
            }
            if (!Schema::hasColumn('inscripcion', 'pagofacil_qr_base64')) {
                $table->text('pagofacil_qr_base64')->nullable()->after('pagofacil_payment_number');
            }
            if (!Schema::hasColumn('inscripcion', 'pagofacil_qr_expira')) {
                $table->timestamp('pagofacil_qr_expira')->nullable()->after('pagofacil_qr_base64');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $columns = [
                'monto_inscripcion',
                'estado_pago',
                'metodo_pago',
                'fecha_pago',
                'numero_recibo',
                'pagofacil_transaction_id',
                'pagofacil_payment_number',
                'pagofacil_qr_base64',
                'pagofacil_qr_expira',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('inscripcion', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
