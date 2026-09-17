<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Docente extends Model
{
    use HasFactory;
    protected $table = 'docente';

    /**
     * Shared PK with usuario — not auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'codigo',
        'estado',
        'fecha_nacimiento',
        'fecha_incorporacion',
        'telefono',
        'observaciones',
    ];

    protected $casts = [
        'fecha_nacimiento'    => 'date',
        'fecha_incorporacion' => 'date',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id', 'id');
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(
            Especialidad::class,
            'especialidad_docente',
            'docente_id',
            'especialidad_id'
        )->withTimestamps();
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'docente_id');
    }

    public function horariosDocente(): HasMany
    {
        return $this->hasMany(HorarioDocente::class, 'docente_id');
    }
}
