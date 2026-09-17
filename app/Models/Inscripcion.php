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
        'observaciones',
    ];

    protected $casts = [
        'fecha'              => 'date',
        'fecha_inicio_clases'=> 'date',
        'fecha_pausa'        => 'date',
        'fecha_retorno'      => 'date',
        'fecha_retiro'       => 'date',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}
