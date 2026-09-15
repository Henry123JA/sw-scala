<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = [
        'nombre',
        'ruta',
        'icono',
        'orden',
        'padre_id',
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

    /**
     * Scope to retrieve root menus only.
     */
    public function scopeRaices($query)
    {
        return $query->whereNull('padre_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(Menu::class, 'padre_id')->orderBy('orden');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_menu', 'menu_id', 'rol_id');
    }
}
