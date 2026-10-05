<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->string('nombre_grupo', 80)->nullable()->after('codigo_grupo');
        });

        $aRomano = static function (int $numero): string {
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
        };

        $nombreAcademico = static function (string $codigo, ?string $grado) use ($aRomano): string {
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

                return $gradoTexto.'-'.$aRomano($grupoNumero);
            }

            // No se inventa un nombre para códigos heredados no reconocidos.
            return trim($codigo);
        };

        DB::table('grupos')
            ->orderBy('id')
            ->get(['id', 'codigo_grupo', 'grado'])
            ->each(function ($grupo) use ($nombreAcademico) {
                DB::table('grupos')
                    ->where('id', $grupo->id)
                    ->update([
                        'nombre_grupo' => $nombreAcademico(
                            (string) $grupo->codigo_grupo,
                            $grupo->grado !== null ? (string) $grupo->grado : null
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropColumn('nombre_grupo');
        });
    }
};
