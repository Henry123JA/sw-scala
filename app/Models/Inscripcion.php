<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscripcion extends Model
{
    use HasFactory;
    protected $table = 'inscripcion';

    protected $fillable = [
        'alumno_id',
        'grupo_id',
        'fecha',
        'fecha_inicio_clases',
        'fecha_pausa',
        'fecha_retorno',
        'fecha_retiro',
        'estado',
        'monto_inscripcion',
        'estado_pago',
        'metodo_pago',
        'fecha_pago',
        'numero_recibo',
        'pagofacil_transaction_id',
        'pagofacil_payment_number',
        'pagofacil_qr_base64',
        'pagofacil_qr_expira',
        'observaciones',
    ];

    protected $casts = [
        'fecha'               => 'date',
        'fecha_inicio_clases' => 'date',
        'fecha_pausa'         => 'date',
        'fecha_retorno'       => 'date',
        'fecha_retiro'        => 'date',
        'monto_inscripcion'   => 'float',
        'fecha_pago'          => 'datetime',
        'pagofacil_qr_expira' => 'datetime',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'inscripcion_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'inscripcion_id');
    }

    public function observacionesAcademicas(): HasMany
    {
        return $this->hasMany(ObservacionAcademica::class, 'inscripcion_id');
    }

    public function alertasSeguimiento(): HasMany
    {
        return $this->hasMany(AlertaSeguimiento::class, 'inscripcion_id');
    }

    public function intervencionesSeguimiento(): HasMany
    {
        return $this->hasMany(IntervencionSeguimiento::class, 'inscripcion_id');
    }
}
