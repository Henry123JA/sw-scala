<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    public const ESTADOS = ['PRESENTE', 'FALTA', 'LICENCIA', 'ATRASO'];

    protected $table = 'asistencia';

    protected $fillable = ['sesion_clase_id', 'inscripcion_id', 'estado', 'observacion'];

    public function sesionClase(): BelongsTo
    {
        return $this->belongsTo(SesionClase::class, 'sesion_clase_id');
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }
}
