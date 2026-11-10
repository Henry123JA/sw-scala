<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VersionModelo extends Model
{
    use HasFactory;

    protected $table = 'version_modelo';

    protected $fillable = [
        'version',
        'nombre',
        'algoritmo',
        'accuracy',
        'f1_score',
        'fecha_entrenamiento',
        'url_endpoint',
        'activo',
        'descripcion',
    ];

    protected $casts = [
        'accuracy'            => 'float',
        'f1_score'            => 'float',
        'fecha_entrenamiento' => 'date',
        'activo'              => 'boolean',
    ];

    public function alertas(): HasMany
    {
        return $this->hasMany(AlertaSeguimiento::class, 'version_modelo_id');
    }
}

