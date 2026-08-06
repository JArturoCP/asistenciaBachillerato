<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocenteGrupo extends Model
{
    use HasFactory;

    protected $table = 'docente_grupo';

    protected $fillable = [
        'docente_id',
        'grupo_id',
        'materia',
        'hora_inicio',
        'hora_fin',
        'dias_semana',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
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
}
