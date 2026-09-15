<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;
    protected $table = 'grupo';

    protected $fillable = [
        'codigo_grupo',
        'curso_id',
        'docente_id',
        'capacidad_maxima',
        'nivel',
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

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'grupo_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioGrupo::class, 'grupo_id');
    }

    public function softDelete(): void
    {
        $this->update(['eliminado' => true]);
    }
}
