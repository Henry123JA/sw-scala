<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioGrupo extends Model
{
    protected $table = 'horario_grupo';

    protected $fillable = [
        'grupo_id',
        'sala_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'tipo_sesion',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'sala_id');
    }
}
