<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Models\AsistenciaDocente;

class InstitutionalRolesAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic user roles and data
        $this->seed();
    }

    public function test_director_can_view_all_student_attendances()
    {
        $director = User::factory()->create([
            'role' => 'director',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($director)->get(route('attendance.overview.index'));

        $response->assertStatus(200);
        $response->assertSee('Control General de Asistencias');
        $response->assertSee('Asistencia de Alumnos');
    }

    public function test_orientador_can_view_student_attendances_and_cannot_view_teachers()
    {
        $orientador = User::factory()->create([
            'role' => 'orientador',
            'is_approved' => true,
        ]);

        // Accessing default student tab
        $response = $this->actingAs($orientador)->get(route('attendance.overview.index', ['tab' => 'students']));
        $response->assertStatus(200);
        $response->assertSee('Asistencia de Alumnos');

        // Trying to switch to teachers tab should default back to students or hide teachers section
        $responseTeachers = $this->actingAs($orientador)->get(route('attendance.overview.index', ['tab' => 'teachers']));
        $responseTeachers->assertStatus(200);
        $responseTeachers->assertSee('Asistencia de Alumnos');
        $responseTeachers->assertDontSee('Asistencia de Docentes');
    }

    public function test_supervisor_can_view_both_student_and_teacher_attendances()
    {
        $supervisor = User::factory()->create([
            'role' => 'supervisor',
            'is_approved' => true,
        ]);

        // Can access student attendances
        $responseStudents = $this->actingAs($supervisor)->get(route('attendance.overview.index', ['tab' => 'students']));
        $responseStudents->assertStatus(200);
        $responseStudents->assertSee('Asistencia de Alumnos');

        // Can access teacher attendances
        $responseTeachers = $this->actingAs($supervisor)->get(route('attendance.overview.index', ['tab' => 'teachers']));
        $responseTeachers->assertStatus(200);
        $responseTeachers->assertSee('Asistencia de Docentes');
    }

    public function test_subdirector_can_update_student_attendance_status()
    {
        $subdirector = User::factory()->create([
            'role' => 'subdirector',
            'is_approved' => true,
        ]);

        $grupo = Grupo::first() ?? Grupo::factory()->create();
        $studentUser = User::factory()->create(['role' => 'student', 'is_approved' => true]);
        $estudiante = Estudiante::create([
            'user_id' => $studentUser->id,
            'grupo_id' => $grupo->id,
            'matricula' => 'MAT999888',
            'is_active' => true,
        ]);

        $today = now()->format('Y-m-d');

        $response = $this->actingAs($subdirector)->post(route('attendance.overview.update-student'), [
            'student_id' => $estudiante->id,
            'date' => $today,
            'status' => 'justificado',
            'notes' => 'Justificante de subdirección',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'estado' => 'justificado',
            'observaciones' => 'Justificante de subdirección',
        ]);
    }

    public function test_institutional_roles_login_redirections()
    {
        $director = User::factory()->create([
            'role' => 'director',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $director->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('attendance.overview.index'));

        // Navigating to /dashboard also redirects gracefully to overview
        $dashResponse = $this->actingAs($director)->get('/dashboard');
        $dashResponse->assertRedirect(route('attendance.overview.index'));
    }
}
