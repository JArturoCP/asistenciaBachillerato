<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Tutor;
use App\Models\DocenteGrupo;
use App\Models\Consentimiento;
use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin User
        $admin = User::create([
            'nombre' => 'Administrador',
            'apellido_paterno' => 'Escolar',
            'apellido_materno' => 'General',
            'email' => 'admin@escuela.edu.mx',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '5551234567',
        ]);

        // 2. Create Groups
        $group1A = Grupo::create(['codigo_grupo' => '1A-MAT', 'grado' => '1', 'turno' => 'matutino', 'ciclo_escolar' => '2026-2027']);
        $group1B = Grupo::create(['codigo_grupo' => '1B-MAT', 'grado' => '1', 'turno' => 'matutino', 'ciclo_escolar' => '2026-2027']);
        $group2A = Grupo::create(['codigo_grupo' => '2A-MAT', 'grado' => '2', 'turno' => 'matutino', 'ciclo_escolar' => '2026-2027']);
        $group3A = Grupo::create(['codigo_grupo' => '3A-VESP', 'grado' => '3', 'turno' => 'vespertino', 'ciclo_escolar' => '2026-2027']);

        // 3. Create Teachers
        $teacher1 = User::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Martínez',
            'apellido_materno' => 'Sánchez',
            'email' => 'docente1@escuela.edu.mx',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'phone' => '5559876543',
        ]);

        $teacher2 = User::create([
            'nombre' => 'Laura',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'Flores',
            'email' => 'docente2@escuela.edu.mx',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'phone' => '5553334444',
        ]);

        // Teacher Assignments
        DocenteGrupo::create(['docente_id' => $teacher1->id, 'grupo_id' => $group1A->id, 'materia' => 'Matemáticas I', 'hora_inicio' => '07:00:00', 'hora_fin' => '08:40:00']);
        DocenteGrupo::create(['docente_id' => $teacher1->id, 'grupo_id' => $group2A->id, 'materia' => 'Matemáticas II', 'hora_inicio' => '08:40:00', 'hora_fin' => '10:20:00']);
        DocenteGrupo::create(['docente_id' => $teacher2->id, 'grupo_id' => $group1A->id, 'materia' => 'Química I', 'hora_inicio' => '08:40:00', 'hora_fin' => '10:20:00']);

        // 4. Create Guardians (Tutores)
        $parentUser1 = User::create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Hernández',
            'email' => 'tutor1@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'phone' => '5551112222',
        ]);

        $parentUser2 = User::create([
            'nombre' => 'María',
            'apellido_paterno' => 'Elena',
            'apellido_materno' => 'Gómez',
            'email' => 'tutor2@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'phone' => '5552223333',
        ]);

        $guardian1 = Tutor::create([
            'user_id' => $parentUser1->id,
            'parentesco' => 'padre',
            'telefono' => '5551112222',
            'correo_notificaciones' => 'tutor1@gmail.com',
            'alertas_correo_activadas' => true,
        ]);

        $guardian2 = Tutor::create([
            'user_id' => $parentUser2->id,
            'parentesco' => 'madre',
            'telefono' => '5552223333',
            'correo_notificaciones' => 'tutor2@gmail.com',
            'alertas_correo_activadas' => true,
        ]);

        // 5. Create Students (Estudiantes) - Person user + Estudiante profile
        $studentsData = [
            ['nombre' => 'Juan Manuel', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'Gómez', 'matricula' => 'BAC-2026-001', 'fecha_nacimiento' => '2009-05-14', 'grupo_id' => $group1A->id],
            ['nombre' => 'Sofía Valentina', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'Gómez', 'matricula' => 'BAC-2026-002', 'fecha_nacimiento' => '2010-08-20', 'grupo_id' => $group1B->id],
            ['nombre' => 'Mateo', 'apellido_paterno' => 'Hernández', 'apellido_materno' => 'Silva', 'matricula' => 'BAC-2026-003', 'fecha_nacimiento' => '2009-01-10', 'grupo_id' => $group1A->id],
            ['nombre' => 'Camila', 'apellido_paterno' => 'López', 'apellido_materno' => 'Morales', 'matricula' => 'BAC-2026-004', 'fecha_nacimiento' => '2008-11-03', 'grupo_id' => $group2A->id],
            ['nombre' => 'Diego Alejandro', 'apellido_paterno' => 'Torres', 'apellido_materno' => 'Ruiz', 'matricula' => 'BAC-2026-005', 'fecha_nacimiento' => '2007-04-18', 'grupo_id' => $group3A->id],
        ];

        foreach ($studentsData as $idx => $sData) {
            $studentUser = User::create([
                'nombre' => $sData['nombre'],
                'apellido_paterno' => $sData['apellido_paterno'],
                'apellido_materno' => $sData['apellido_materno'],
                'role' => 'student',
            ]);

            $student = Estudiante::create([
                'user_id' => $studentUser->id,
                'matricula' => $sData['matricula'],
                'fecha_nacimiento' => $sData['fecha_nacimiento'],
                'grupo_id' => $sData['grupo_id'],
            ]);

            // Link first 2 students to guardian1, 3rd to guardian2
            if ($idx < 2) {
                $guardian1->estudiantes()->attach($student->id, ['es_contacto_principal' => true, 'fecha_verificacion' => now()]);
                Consentimiento::create([
                    'tutor_id' => $guardian1->id,
                    'estudiante_id' => $student->id,
                    'version_consentimiento' => 'v1.0-LFPDPPP',
                    'aceptado' => true,
                    'fecha_otorgado' => now(),
                ]);
            } elseif ($idx === 2) {
                $guardian2->estudiantes()->attach($student->id, ['es_contacto_principal' => true, 'fecha_verificacion' => now()]);
                Consentimiento::create([
                    'tutor_id' => $guardian2->id,
                    'estudiante_id' => $student->id,
                    'version_consentimiento' => 'v1.0-LFPDPPP',
                    'aceptado' => true,
                    'fecha_otorgado' => now(),
                ]);
            }
        }

        AuditLog::log('WRITE', 'users', $admin->id, 'Seeder normalizado en español ejecutado exitosamente');
    }
}
