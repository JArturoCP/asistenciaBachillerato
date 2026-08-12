<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Tutor;
use App\Models\Consentimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_superadmin_can_access_all_modules()
    {
        $superadmin = User::where('role', 'superadmin')->first();

        $this->actingAs($superadmin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($superadmin)->get(route('admin.groups.index'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('teacher.attendance.index'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('parent.dashboard'))->assertStatus(200);
    }

    public function test_admin_can_access_dashboard()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Panel de Control Principal');
    }

    public function test_parent_cannot_access_dashboard_and_receives_403()
    {
        $parent = User::where('role', 'parent')->first();

        $response = $this->actingAs($parent)->get('/dashboard');
        $response->assertStatus(403);
    }

    public function test_teacher_cannot_access_dashboard_and_receives_403()
    {
        $teacher = User::where('role', 'teacher')->first();

        $response = $this->actingAs($teacher)->get('/dashboard');
        $response->assertStatus(403);
    }

    public function test_role_based_login_redirections()
    {
        // 1. Parent login redirects to /parent/dashboard
        $responseParent = $this->post('/login', [
            'email' => 'tutor1@gmail.com',
            'password' => 'password',
        ]);
        $responseParent->assertRedirect(route('parent.dashboard'));

        $this->post('/logout');

        // 2. Teacher login redirects to /teacher/attendance
        $responseTeacher = $this->post('/login', [
            'email' => 'docente1@escuela.edu.mx',
            'password' => 'password',
        ]);
        $responseTeacher->assertRedirect(route('teacher.attendance.index'));

        $this->post('/logout');

        // 3. Admin login redirects to /dashboard
        $responseAdmin = $this->post('/login', [
            'email' => 'admin@escuela.edu.mx',
            'password' => 'password',
        ]);
        $responseAdmin->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_group()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.groups.store'), [
            'group_code' => '4A-MAT',
            'grade' => '3',
            'shift' => 'matutino',
            'school_year' => '2026-2027',
        ]);

        $response->assertRedirect(route('admin.groups.index'));
        $this->assertDatabaseHas('grupos', ['codigo_grupo' => '4A-MAT']);
    }

    public function test_student_auto_generates_uuid_for_qr()
    {
        $group = Grupo::first();
        $user = User::create([
            'nombre' => 'Prueba',
            'apellido_paterno' => 'Alumno',
            'role' => 'student',
        ]);

        $student = Estudiante::create([
            'user_id' => $user->id,
            'matricula' => 'BAC-TEST-999',
            'grupo_id' => $group->id,
        ]);

        $this->assertNotEmpty($student->uuid);
        $this->assertEquals(36, strlen($student->uuid));
    }

    public function test_admin_can_view_student_credential()
    {
        $admin = User::where('role', 'admin')->first();
        $student = Estudiante::first();

        $response = $this->actingAs($admin)->get(route('admin.students.credential', $student));
        $response->assertStatus(200);
        $response->assertSee($student->uuid);
        $response->assertSee('Credencial Estudiantil');
    }

    public function test_admin_can_link_guardian_and_register_lfpdppp_consent()
    {
        $admin = User::where('role', 'admin')->first();
        $guardian = Tutor::first();
        $group = Grupo::first();

        $user = User::create([
            'nombre' => 'Nuevo',
            'apellido_paterno' => 'Alumno',
            'role' => 'student',
        ]);

        $student = Estudiante::create([
            'user_id' => $user->id,
            'matricula' => 'BAC-TEST-888',
            'grupo_id' => $group->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.guardians.linkStudent'), [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'consent_accepted' => 1,
        ]);

        $response->assertRedirect(route('admin.guardians.index'));
        $this->assertDatabaseHas('estudiante_tutor', [
            'tutor_id' => $guardian->id,
            'estudiante_id' => $student->id,
        ]);
        $this->assertDatabaseHas('consentimientos', [
            'tutor_id' => $guardian->id,
            'estudiante_id' => $student->id,
            'aceptado' => true,
        ]);
    }

    public function test_teacher_user_cannot_access_admin_routes()
    {
        $teacher = User::where('role', 'teacher')->first();

        $response = $this->actingAs($teacher)->get(route('admin.groups.index'));
        $response->assertStatus(403);
    }
}
