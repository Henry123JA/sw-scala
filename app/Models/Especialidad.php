<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Especialidad extends Model
{
    protected $table = 'especialidad';

    protected $fillable = [
        'nombre',
        'descripcion',
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

    public function softDelete(): void
    {
        $this->update(['eliminado' => true]);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Docente::class,
            'especialidad_docente',
            'especialidad_id',
            'docente_id'
        )->withTimestamps();
    }
}
