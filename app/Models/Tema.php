<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tema extends Model
{
    protected $table = 'tema';

    protected $fillable = [
        'nombre',
        'font_size',
        'descripcion',
        'color_primario_dia',
        'color_secundario_dia',
        'contraste_dia',
        'color_primario_noche',
        'color_secundario_noche',
        'contraste_noche',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'tema_id');
    }
}
