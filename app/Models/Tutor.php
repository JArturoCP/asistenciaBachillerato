<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tutor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tutores';

    protected $fillable = [
        'user_id',
        'parentesco',
        'telefono',
        'correo_notificaciones',
        'alertas_correo_activadas',
        'alertas_whatsapp_activadas',
    ];

    protected function casts(): array
    {
        return [
            'alertas_correo_activadas' => 'boolean',
            'alertas_whatsapp_activadas' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_tutor', 'tutor_id', 'estudiante_id')
                    ->withPivot('fecha_verificacion')
                    ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->estudiantes();
    }

    public function consentimientos(): HasMany
    {
        return $this->hasMany(Consentimiento::class, 'tutor_id');
    }

    public function consents(): HasMany
    {
        return $this->consentimientos();
    }
}
