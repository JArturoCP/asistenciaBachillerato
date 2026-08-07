<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('materias')->onDelete('cascade');
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');
            $table->enum('dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'])->default('lunes');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('aula')->nullable(); // e.g. Aula 101, Lab de Química
            $table->timestamps();
        });

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->enum('estado', ['presente', 'retardo', 'falta', 'justificado'])->default('presente');
            $table->enum('metodo_escaneo', ['qr_camera', 'qr_usb', 'manual_admin'])->default('qr_usb');
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('consentimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('tutores')->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->string('version_consentimiento')->default('v1.0-LFPDPPP');
            $table->boolean('aceptado')->default(true);
            $table->string('ip_address')->nullable();
            $table->timestamp('fecha_otorgado')->useCurrent();
            $table->timestamp('fecha_revocado')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('action'); // READ, WRITE, EXPORT, LOGIN, ARCO_REQUEST
                $table->string('target_table')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('consentimientos');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('docente_grupo');
    }
};
