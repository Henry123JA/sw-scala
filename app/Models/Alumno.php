<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumno extends Model
{
    use HasFactory;
    protected $table = 'alumno';

    /**
     * Shared PK with usuario — not auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'codigo',
        'estado',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'telefono_alternativo',
        'nivel',
        'referido_por',
        'observaciones',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Global scope: only non-deleted users via the usuario table.
     * The alumno table has no eliminado column itself; deletion is on usuario.
     */
    protected static function booted(): void
    {
        // No eliminado column on alumno — soft-delete is on usuario.
        // The global scope is not needed here, but we exclude via join
        // in the service layer. Leave booted clean.
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id', 'id');
    }

    public function referidoPor(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'referido_por');
    }

    public function referidos(): HasMany
    {
        return $this->hasMany(Alumno::class, 'referido_por');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'alumno_id');
    }
}
