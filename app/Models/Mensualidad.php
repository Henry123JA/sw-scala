<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensualidad extends Model
{
    use HasFactory;
    protected $table = 'mensualidad';

    protected $fillable = [
        'inscripcion_id',
        'metodo_pago_id',
        'numero_mes',
        'meses_pagados',
        'monto_base',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'numero_recibo',
        'tipo_comprobante',
        'comprobante',
        'observaciones',
        'pagofacil_transaction_id',
        'pagofacil_payment_number',
        'pagofacil_qr_base64',
        'pagofacil_qr_expira',
    ];

    protected $casts = [
        'fecha_vencimiento'   => 'date',
        'fecha_pago'          => 'date',
        'monto_base'          => 'decimal:2',
        'meses_pagados'       => 'integer',
        'pagofacil_qr_expira' => 'datetime',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
