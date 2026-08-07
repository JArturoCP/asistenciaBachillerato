<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consentimiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consentimientos';

    protected $fillable = [
        'tutor_id',
        'estudiante_id',
        'version_consentimiento',
        'aceptado',
        'ip_address',
        'fecha_otorgado',
        'fecha_revocado',
    ];

    protected function casts(): array
    {
        return [
            'aceptado' => 'boolean',
            'fecha_otorgado' => 'datetime',
            'fecha_revocado' => 'datetime',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function getGrantedAtAttribute()
    {
        return $this->fecha_otorgado;
    }
}
