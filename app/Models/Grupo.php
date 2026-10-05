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
        'nombre_grupo',
        'grado',
        'turno',
        'ciclo_escolar',
    ];


    /**
     * Nombre académico almacenado en BD.
     * Ejemplos: G-1-2 => Primero-II, G-3-1 => Tercer-I.
     */
    public function getNombreCredencialAttribute(): string
    {
        return $this->nombre_grupo ?: self::generarNombreAcademico(
            (string) $this->codigo_grupo,
            (string) $this->grado
        );
    }


    /**
     * Nombre que debe mostrarse en interfaces de usuario.
     * Prioriza el valor almacenado en BD y conserva compatibilidad
     * con registros anteriores a la columna nombre_grupo.
     */
    public function getNombreVisibleAttribute(): string
    {
        return $this->nombre_grupo ?: $this->nombre_credencial;
    }

    public static function generarNombreAcademico(string $codigo, ?string $grado = null): string
    {
        $codigoNormalizado = strtoupper(trim($codigo));

        if (preg_match('/^(?:G-)?(\d+)-(\d+)$/', $codigoNormalizado, $matches)) {
            $gradoNumero = (int) $matches[1];
            $grupoNumero = (int) $matches[2];

            $gradoTexto = match ($gradoNumero) {
                1 => 'Primero',
                2 => 'Segundo',
                3 => 'Tercer',
                default => $grado ?: (string) $gradoNumero,
            };

            return $gradoTexto.'-'.self::numeroARomano($grupoNumero);
        }

        return trim($codigo) !== '' ? trim($codigo) : (string) $grado;
    }

    private static function numeroARomano(int $numero): string
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
