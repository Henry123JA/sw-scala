<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioDocente extends Model
{
    protected $table = 'horario_docente';

    protected $fillable = [
        'docente_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }
}
