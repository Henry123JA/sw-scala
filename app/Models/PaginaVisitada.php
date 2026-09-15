<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaginaVisitada extends Model
{
    protected $table = 'pagina_visitada';

    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'usuario_id',
        'contador',
        'ultima_visita',
    ];

    protected $casts = [
        'ultima_visita' => 'datetime',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
