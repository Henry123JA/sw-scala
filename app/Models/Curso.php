<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;
    protected $table = 'curso';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_ensenanza',
        'duracion_estandar',
        'estado',
        'eliminado',
    ];

    protected $casts = [
        'eliminado' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('activo', function ($query) {
            $query->where('eliminado', false);
        });
    }

    public function grupos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grupo::class, 'curso_id');
    }

    public function softDelete(): void
    {
        $this->update(['eliminado' => true]);
    }
}

