<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grupos';

    protected $fillable = [
        'codigo_grupo',
        'grado',
        'turno',
        'ciclo_escolar',
    ];


    /**
     * Nombre académico del grupo para credenciales.
     * Ejemplos:
     * G-1-2 => Primero-II
     * G-3-1 => Tercer-I
     */
    public function getNombreCredencialAttribute(): string
    {
        $codigo = strtoupper(trim((string) $this->codigo_grupo));

        // Acepta G-1-2 y también 1-2.
        if (preg_match('/^(?:G-)?(\d+)-(\d+)$/', $codigo, $matches)) {
            $gradoNumero = (int) $matches[1];
            $grupoNumero = (int) $matches[2];

            $gradoTexto = match ($gradoNumero) {
                1 => 'Primero',
                2 => 'Segundo',
                3 => 'Tercer',
                default => $this->grado ?: (string) $gradoNumero,
            };

            return $gradoTexto . '-' . $this->numeroARomano($grupoNumero);
        }

        // Si el código no sigue el patrón esperado, conservar el código real
        // para no mostrar información inventada.
        return $this->codigo_grupo;
    }

    private function numeroARomano(int $numero): string
    {
        if ($numero <= 0) {
            return (string) $numero;
        }

        $mapa = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];

        $romano = '';
        foreach ($mapa as $valor => $simbolo) {
            while ($numero >= $valor) {
                $romano .= $simbolo;
                $numero -= $valor;
            }
        }

        return $romano;
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'grupo_id');
    }

    public function docenteGrupos(): HasMany
    {
        return $this->hasMany(DocenteGrupo::class, 'grupo_id');
    }
}
