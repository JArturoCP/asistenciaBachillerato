<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('parentesco')->default('padre'); // padre, madre, tutor_legal
            $table->string('telefono')->nullable();
            $table->string('correo_notificaciones')->nullable();
            $table->boolean('alertas_correo_activadas')->default(true);
            $table->timestamps();
        });

        Schema::create('estudiante_tutor', function (Blueprint $table) {
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('tutores')->onDelete('cascade');
            $table->boolean('es_contacto_principal')->default(true);
            $table->timestamp('fecha_verificacion')->nullable();
            $table->timestamps();
            $table->primary(['estudiante_id', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_tutor');
        Schema::dropIfExists('tutores');
    }
};
