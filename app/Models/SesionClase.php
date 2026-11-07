<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionClase extends Model
{
    protected $table = 'sesion_clase';

    protected $fillable = ['grupo_id', 'fecha'];

    protected $casts = ['fecha' => 'date'];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}
