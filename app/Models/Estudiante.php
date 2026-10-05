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
                if (empty($estudiante->grupo_id)) {
                    throw new \InvalidArgumentException('No es posible generar una matrícula automática sin grupo.');
                }
                $estudiante->matricula = self::generateNextMatricula((int) $estudiante->grupo_id);
            }
        });
    }

    /**
     * Formato automático: AÑO-GRUPO-CONSECUTIVO.
     * Ejemplo para Grupo 1-3 en 2026: 2026-1-3-001.
     */
    public static function generateNextMatricula(Grupo|int|null $group = null, ?int $year = null): string
    {
        $year ??= (int) date('Y');

        // Únicamente para textos de ayuda en la interfaz.
        if ($group === null) {
            return sprintf('%d-GRUPO-%03d', $year, 1);
        }

        $group = is_int($group) ? Grupo::findOrFail($group) : $group;
        $sequence = self::nextMatriculaSequence($group, $year);

        return self::formatMatricula($group, $sequence, $year);
    }

    public static function nextMatriculaSequence(Grupo|int $group, ?int $year = null): int
    {
        $year ??= (int) date('Y');
        $group = is_int($group) ? Grupo::findOrFail($group) : $group;
        $prefix = self::matriculaPrefix($group, $year);
        $max = 0;

        $matriculas = self::withTrashed()
            ->where('matricula', 'like', $prefix.'%')
            ->pluck('matricula');

        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/D';
        foreach ($matriculas as $matricula) {
            if (preg_match($pattern, (string) $matricula, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    public static function formatMatricula(Grupo|string $group, int $sequence, ?int $year = null): string
    {
        $year ??= (int) date('Y');
        return sprintf('%s%03d', self::matriculaPrefix($group, $year), $sequence);
    }

    public static function matriculaPrefix(Grupo|string $group, ?int $year = null): string
    {
        $year ??= (int) date('Y');
        return $year.'-'.self::matriculaGroupToken($group).'-';
    }

    /**
     * Convierte códigos como:
     * - "Grupo 1-3 (Turno Matutino)" -> "1-3"
     * - "1-3" -> "1-3"
     * - "1A-MAT" -> "1A-MAT"
     */
    public static function matriculaGroupToken(Grupo|string $group): string
    {
        $raw = $group instanceof Grupo ? $group->codigo_grupo : $group;
        $raw = trim((string) $raw);

        if (preg_match('/\bgrupo\s+([A-Za-z0-9]+(?:[-_][A-Za-z0-9]+)*)/iu', $raw, $matches)) {
            $raw = $matches[1];
        } else {
            $raw = preg_replace('/\s*\(.*$/u', '', $raw) ?? $raw;
            $raw = preg_replace('/^grupo\s+/iu', '', $raw) ?? $raw;
        }

        $token = strtoupper(Str::ascii($raw));
        $token = preg_replace('/[^A-Z0-9]+/', '-', $token) ?? '';
        $token = trim($token, '-');

        return $token !== '' ? $token : 'GRUPO';
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->user ? $this->user->nombre_completo : '';
    }

    public function getNombreFormateadoAttribute(): string
    {
        return $this->user ? $this->user->nombre_formateado : '';
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
                    ->withPivot('fecha_verificacion')
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
