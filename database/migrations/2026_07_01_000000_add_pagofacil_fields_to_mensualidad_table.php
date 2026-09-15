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
        Schema::table('mensualidad', function (Blueprint $table) {
            $table->string('pagofacil_transaction_id')->nullable();
            $table->string('pagofacil_payment_number')->nullable()->index();
            $table->text('pagofacil_qr_base64')->nullable();
            $table->timestamp('pagofacil_qr_expira')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensualidad', function (Blueprint $table) {
            $table->dropColumn([
                'pagofacil_transaction_id',
                'pagofacil_payment_number',
                'pagofacil_qr_base64',
                'pagofacil_qr_expira',
            ]);
        });
    }
};
