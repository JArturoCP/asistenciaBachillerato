<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_grupo')->unique(); // e.g. 1A-MAT
            $table->string('grado'); // e.g. 1, 2, 3
            $table->enum('turno', ['matutino', 'vespertino'])->default('matutino');
            $table->string('ciclo_escolar'); // e.g. 2026-2027
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
