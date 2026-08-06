<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';

    protected $fillable = [
        'user_id',
        'uuid',
        'matricula',
        'fecha_nacimiento',
        'grupo_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($estudiante) {
            if (empty($estudiante->uuid)) {
                $estudiante->uuid = (string) Str::uuid();
            }
        });
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->user ? $this->user->nombre_completo : '';
    }

    public function getFullNameAttribute(): string
    {
        return $this->nombre_completo;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    // Alias for backward compatibility
    public function group(): BelongsTo
    {
        return $this->grupo();
    }

    public function tutores(): BelongsToMany
    {
        return $this->belongsToMany(Tutor::class, 'estudiante_tutor', 'estudiante_id', 'tutor_id')
                    ->withPivot('es_contacto_principal', 'fecha_verificacion')
                    ->withTimestamps();
    }

    // Alias for backward compatibility
    public function guardians(): BelongsToMany
    {
        return $this->tutores();
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'estudiante_id');
    }

    public function attendances(): HasMany
    {
        return $this->asistencias();
    }

    public function consentimientos(): HasMany
    {
        return $this->hasMany(Consentimiento::class, 'estudiante_id');
    }

    public function consents(): HasMany
    {
        return $this->consentimientos();
    }
}
