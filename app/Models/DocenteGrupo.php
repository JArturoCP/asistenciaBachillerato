<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocenteGrupo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'docente_grupo';

    protected $fillable = [
        'docente_id',
        'materia_id',
        'grupo_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'aula',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function getSubjectNameAttribute()
    {
        return $this->materia?->nombre ?? 'Sin Asignatura';
    }
}
