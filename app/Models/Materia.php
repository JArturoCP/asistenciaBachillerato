<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'materias';

    protected $fillable = [
        'clave',
        'nombre',
        'semestre',
    ];

    public function docenteGrupos(): HasMany
    {
        return $this->hasMany(DocenteGrupo::class, 'materia_id');
    }
}
