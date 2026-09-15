<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'permiso';

    protected $fillable = [
        'nombre',
        'slug',
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }
}
