<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervencionSeguimiento extends Model
{
    use HasFactory;

    protected $table = 'intervencion_seguimiento';

    public const TIPOS = [
        'TUTORIA_INDIVIDUAL',
        'REFUERZO_TECNICO',
        'CITACION_PADRES',
        'LLAMADA_SEGUIMIENTO',
        'REPROGRAMACION',
    ];

    public const RESULTADOS = [
        'EN_PROCESO',
        'MEJORA_EVIDENTE',
        'SIN_CAMBIOS',
        'REQUIERE_ESCALAMIENTO',
    ];

    protected $fillable = [
        'alerta_id',
        'inscripcion_id',
        'usuario_id',
        'fecha',
        'tipo',
        'titulo',
        'descripcion',
        'compromiso',
        'resultado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function alerta(): BelongsTo
    {
        return $this->belongsTo(AlertaSeguimiento::class, 'alerta_id');
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}

