<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertaSeguimiento extends Model
{
    use HasFactory;

    protected $table = 'alerta_seguimiento';

    public const NIVELES_RIESGO = ['ALTO', 'MEDIO', 'BAJO'];
    public const ESTADOS = ['PENDIENTE', 'EN_ATENCION', 'RESUELTA', 'DESCARTADA'];
    public const ORIGENES = ['ML_API', 'N8N_AUTO', 'MOTOR_LOCAL'];

    protected $fillable = [
        'inscripcion_id',
        'version_modelo_id',
        'nivel_riesgo',
        'score_riesgo',
        'factores_riesgo',
        'metricas_base',
        'resumen_explicativo',
        'estado',
        'origen',
        'fecha_analisis',
    ];

    protected $casts = [
        'score_riesgo'    => 'float',
        'factores_riesgo' => 'array',
        'metricas_base'   => 'array',
        'fecha_analisis'  => 'datetime',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function versionModelo(): BelongsTo
    {
        return $this->belongsTo(VersionModelo::class, 'version_modelo_id');
    }

    public function intervenciones(): HasMany
    {
        return $this->hasMany(IntervencionSeguimiento::class, 'alerta_id');
    }
}

