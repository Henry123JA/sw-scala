<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservacionAcademica extends Model
{
    use HasFactory;

    protected $table = 'observacion_academica';

    public const TIPO_PROGRESO_TECNICO = 'PROGRESO_TECNICO';
    public const TIPO_RECOMENDACION = 'RECOMENDACION';
    public const TIPO_DISCIPLINA = 'DISCIPLINA';
    public const TIPO_FELICITACION = 'FELICITACION';

    public const TIPOS = [
        self::TIPO_PROGRESO_TECNICO,
        self::TIPO_RECOMENDACION,
        self::TIPO_DISCIPLINA,
        self::TIPO_FELICITACION,
    ];

    protected $fillable = [
        'inscripcion_id',
        'docente_id',
        'fecha',
        'tipo',
        'titulo',
        'descripcion',
        'visible_para_alumno',
    ];

    protected $casts = [
        'fecha' => 'date',
        'visible_para_alumno' => 'boolean',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }
}
