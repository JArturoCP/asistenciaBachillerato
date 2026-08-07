<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Estudiante extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'estudiantes';

    protected $fillable = [
        'user_id',
        'uuid',
        'matricula',
        'foto',
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
            if (empty($estudiante->matricula)) {
                $estudiante->matricula = self::generateNextMatricula();
            }
        });
    }

    public static function generateNextMatricula(): string
    {
        $year = date('Y');
        $prefix = "BAC-{$year}-";

        $maxNum = 0;
        $matriculas = self::withTrashed()
            ->where('matricula', 'like', "{$prefix}%")
            ->pluck('matricula');

        foreach ($matriculas as $m) {
            if (preg_match('/BAC-\d{4}-(\d+)/', $m, $matches)) {
                $num = intval($matches[1]);
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        if ($maxNum === 0) {
            $count = self::withTrashed()->count();
            $nextNum = $count + 1;
        } else {
            $nextNum = $maxNum + 1;
        }

        return sprintf("BAC-%s-%03d", $year, $nextNum);
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
