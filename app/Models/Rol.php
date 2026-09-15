<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'rol';

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

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'rol_menu', 'rol_id', 'menu_id');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }
}
