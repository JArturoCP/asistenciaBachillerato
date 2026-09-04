<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaDocente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asistencias_docentes';

    protected $fillable = [
        'docente_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'estado',
        'metodo_escaneo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function user(): BelongsTo
    {
        return $this->docente();
    }
}
