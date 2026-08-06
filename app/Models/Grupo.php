<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    protected $fillable = [
        'codigo_grupo',
        'grado',
        'turno',
        'ciclo_escolar',
    ];

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'grupo_id');
    }

    public function docenteGrupos(): HasMany
    {
        return $this->hasMany(DocenteGrupo::class, 'grupo_id');
    }
}
