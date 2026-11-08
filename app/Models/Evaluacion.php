<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluacion';

    public const TIPOS = ['PRACTICA', 'TEORIA', 'AUDICION', 'RECITAL'];

    protected $fillable = [
        'grupo_id',
        'nombre',
        'tipo',
        'fecha',
        'ponderacion',
        'descripcion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'ponderacion' => 'decimal:2',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'evaluacion_id');
    }
}
