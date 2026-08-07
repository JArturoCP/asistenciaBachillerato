<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asistencias';

    protected $fillable = [
        'estudiante_id',
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

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function student(): BelongsTo
    {
        return $this->estudiante();
    }

    public function getCheckInTimeAttribute()
    {
        return $this->hora_entrada;
    }

    public function getCheckOutTimeAttribute()
    {
        return $this->hora_salida;
    }

    public function getStatusAttribute()
    {
        return $this->estado;
    }

    public function getNotesAttribute()
    {
        return $this->observaciones;
    }

    public function getDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value) : null;
    }
}
