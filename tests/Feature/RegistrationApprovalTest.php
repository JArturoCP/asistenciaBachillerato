<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_requires_admin_approval_and_blocks_auto_login(): void
    {
        $response = $this->post('/register', [
            'nombre' => 'Pedro',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'Castillo',
            'email' => 'pedro.pending@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'parent',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user = User::where('email', 'pedro.pending@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_approved);
        $this->assertGuest();
    }

    public function test_unapproved_user_login_attempt_is_blocked(): void
    {
        $user = User::create([
            'nombre' => 'Ana',
            'apellido_paterno' => 'Navarro',
            'email' => 'ana.pending@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'is_approved' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'ana.pending@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_admin_can_view_pending_registrations(): void
    {
        $admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'User',
            'email' => 'admin.test@escuela.edu.mx',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        User::create([
            'nombre' => 'Solicitante',
            'apellido_paterno' => 'Prueba',
            'email' => 'solicitante@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'parent',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pending-registrations.index'));
        $response->assertStatus(200);
        $response->assertSee('solicitante@gmail.com');
    }

    public function test_admin_can_approve_pending_registration(): void
    {
        $admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'User',
            'email' => 'admin.test@escuela.edu.mx',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $pendingUser = User::create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Martínez',
            'email' => 'carlos.pending@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'parent',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pending-registrations.approve', $pendingUser));
        $response->assertRedirect(route('admin.pending-registrations.index'));

        $this->assertTrue($pendingUser->fresh()->is_approved);

        // Verify approved user can now log in
        $this->post('/logout');
        $loginResponse = $this->post('/login', [
            'email' => 'carlos.pending@gmail.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($pendingUser);
    }

    public function test_admin_can_reject_and_soft_delete_pending_registration(): void
    {
        $admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'User',
            'email' => 'admin.test@escuela.edu.mx',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $pendingUser = User::create([
            'nombre' => 'Rechazado',
            'apellido_paterno' => 'Prueba',
            'email' => 'rechazado@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.pending-registrations.reject', $pendingUser));
        $response->assertRedirect(route('admin.pending-registrations.index'));

        $this->assertSoftDeleted('users', [
            'email' => 'rechazado@gmail.com',
        ]);
    }

    public function test_admin_creating_user_via_crud_sets_is_approved_to_true_by_default(): void
    {
        $admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'User',
            'email' => 'admin.crud@escuela.edu.mx',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'nombre' => 'Docente',
            'apellido_paterno' => 'CreadoPorAdmin',
            'email' => 'docente.crud@escuela.edu.mx',
            'password' => 'password123',
            'phone' => '5551112233',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));

        $createdTeacher = User::where('email', 'docente.crud@escuela.edu.mx')->first();
        $this->assertNotNull($createdTeacher);
        $this->assertTrue($createdTeacher->is_approved);
    }

    public function test_unapproved_teachers_and_guardians_do_not_appear_in_admin_index_lists(): void
    {
        $admin = User::create([
            'nombre' => 'Admin',
            'apellido_paterno' => 'User',
            'email' => 'admin.test@escuela.edu.mx',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $unapprovedTeacher = User::create([
            'nombre' => 'DocenteNoAprobado',
            'apellido_paterno' => 'Oculto',
            'email' => 'docente.oculto@escuela.edu.mx',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_approved' => false,
        ]);

        $unapprovedParentUser = User::create([
            'nombre' => 'TutorNoAprobado',
            'apellido_paterno' => 'Oculto',
            'email' => 'tutor.oculto@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'parent',
            'is_approved' => false,
        ]);
        Tutor::create([
            'user_id' => $unapprovedParentUser->id,
            'parentesco' => 'padre',
            'telefono' => '5550001111',
            'correo_notificaciones' => 'tutor.oculto@gmail.com',
        ]);

        // Assert unapproved teacher is excluded from Teachers list
        $teacherResponse = $this->actingAs($admin)->get(route('admin.teachers.index'));
        $teacherResponse->assertStatus(200);
        $teacherResponse->assertDontSee('docente.oculto@escuela.edu.mx');

        // Assert unapproved guardian is excluded from Guardians list
        $guardianResponse = $this->actingAs($admin)->get(route('admin.guardians.index'));
        $guardianResponse->assertStatus(200);
        $guardianResponse->assertDontSee('tutor.oculto@gmail.com');
    }
}
