<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuario';

    protected $fillable = [
        'nombres',
        'apellidos',
        'ci',
        'codigo',
        'email',
        'password',
        'rol_id',
        'tema_id',
        'foto',
        'eliminado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'eliminado' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Cache whether the remember_token column exists.
     * Avoids a DB schema query on every auth call.
     */
    protected static ?bool $hasRememberToken = null;

    /**
     * Override to prevent crash when remember_token column doesn't exist yet.
     * Once the migration is applied, this returns the standard column name.
     */
    public function getRememberTokenName(): ?string
    {
        if (static::$hasRememberToken === null) {
            static::$hasRememberToken = \Illuminate\Support\Facades\Schema::hasColumn(
                $this->getTable(),
                'remember_token'
            );
        }

        return static::$hasRememberToken ? 'remember_token' : null;
    }

    /**
     * No-op when remember_token column is absent.
     */
    public function setRememberToken($value): void
    {
        if ($this->getRememberTokenName() !== null) {
            parent::setRememberToken($value);
        }
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('activo', function ($query) {
            $query->where('eliminado', false);
        });
    }

    /**
     * Perform custom soft delete.
     */
    public function softDelete(): void
    {
        $this->update(['eliminado' => true]);
    }

    // Relationships

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function tema(): BelongsTo
    {
        return $this->belongsTo(Tema::class, 'tema_id');
    }

    public function alumno(): HasOne
    {
        return $this->hasOne(Alumno::class, 'id');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'id');
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'usuario_id');
    }

    public function paginasVisitadas(): HasMany
    {
        return $this->hasMany(PaginaVisitada::class, 'usuario_id');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /**
     * Resolves the stored relative foto path to a public URL.
     * Returns null when no photo is set, so frontends can fall back to initials.
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto)) {
            return null;
        }
        return asset('storage/' . $this->foto);
    }
}
