<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'password',
        'role',
        'phone',
        'is_approved',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::saving(function ($user) {
            if ($user->isDirty('name') && !$user->isDirty('nombre')) {
                $parts = explode(' ', trim($user->name), 3);
                $user->nombre = $parts[0] ?? '';
                $user->apellido_paterno = $parts[1] ?? '';
                $user->apellido_materno = $parts[2] ?? null;
            }
            $user->name = trim("{$user->nombre} {$user->apellido_paterno} {$user->apellido_materno}");
        });
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function getNombreFormateadoAttribute(): string
    {
        $apellidos = trim("{$this->apellido_paterno} {$this->apellido_materno}");
        return $apellidos ? "{$apellidos}, {$this->nombre}" : $this->nombre;
    }

    public function getNameAttribute($value): string
    {
        return $value ?: $this->nombre_completo;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'superadmin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher' || $this->role === 'docente';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent' || $this->role === 'tutor';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student' || $this->role === 'estudiante';
    }

    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class);
    }

    public function tutor(): HasOne
    {
        return $this->hasOne(Tutor::class);
    }

    public function docenteGrupos(): HasMany
    {
        return $this->hasMany(DocenteGrupo::class, 'docente_id');
    }

    public function asistenciasDocente(): HasMany
    {
        return $this->hasMany(AsistenciaDocente::class, 'docente_id');
    }
}
