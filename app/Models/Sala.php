<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sala extends Model
{
    protected $table = 'sala';

    protected $fillable = [
        'nombre',
        'tipo',
        'capacidad',
        'ubicacion_piso',
        'equipamiento',
        'estado',
        'eliminado',
    ];

    protected $casts = [
        'eliminado' => 'boolean',
        'capacidad' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('activo', function ($query) {
            $query->where('eliminado', false);
        });
    }

    public function softDelete(): void
    {
        $this->update(['eliminado' => true]);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioGrupo::class, 'sala_id');
    }
}
