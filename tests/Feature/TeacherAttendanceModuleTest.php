<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\AsistenciaDocente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAttendanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_scan_attendance_at_kiosk(): void
    {
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 9, 4, 7, 30, 0, 'America/Mexico_City'));

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($teacher)->postJson(route('scan.process'), [
            'qr_code' => (string) $teacher->id,
            'scan_method' => 'qr_camera',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('student.group', 'Personal Docente');

        $this->assertDatabaseHas('asistencias_docentes', [
            'docente_id' => $teacher->id,
            'metodo_escaneo' => 'qr_camera',
        ]);

        \Carbon\Carbon::setTestNow();
    }

    public function test_admin_can_access_teacher_attendance_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);

        $response = $this->actingAs($admin)->get(route('admin.teacher-attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('Control de Asistencia del Personal Docente');
    }

    public function test_teacher_and_parent_cannot_access_admin_teacher_attendance_panel(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'is_approved' => true]);
        $parent = User::factory()->create(['role' => 'parent', 'is_approved' => true]);

        $this->actingAs($teacher)->get(route('admin.teacher-attendance.index'))->assertStatus(403);
        $this->actingAs($parent)->get(route('admin.teacher-attendance.index'))->assertStatus(403);
    }

    public function test_admin_can_update_teacher_attendance_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'is_approved' => true]);

        $response = $this->actingAs($admin)->post(route('admin.teacher-attendance.update'), [
            'docente_id' => $teacher->id,
            'date' => now('America/Mexico_City')->format('Y-m-d'),
            'status' => 'justificado',
            'notes' => 'Comisión oficial',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('asistencias_docentes', [
            'docente_id' => $teacher->id,
            'estado' => 'justificado',
            'observaciones' => 'Comisión oficial',
        ]);
    }

    public function test_admin_can_export_teacher_attendance_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);

        $response = $this->actingAs($admin)->get(route('admin.teacher-attendance.export', [
            'date' => now('America/Mexico_City')->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition'), 'attachment; filename='));
    }

    public function test_credential_renders_front_and_back_sides(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);
        $grupo = Grupo::create([
            'codigo_grupo' => '101',
            'grado' => '1',
            'grupo' => 'A',
            'turno' => 'matutino',
            'ciclo_escolar' => '2026-2027',
        ]);
        $user = User::factory()->create(['role' => 'student', 'is_approved' => true]);
        $student = Estudiante::create([
            'user_id' => $user->id,
            'grupo_id' => $grupo->id,
            'matricula' => '2026101001',
            'curp' => 'TEST123456HDFRRR01',
            'uuid' => 'test-uuid-1234',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.credential', $student));

        $response->assertStatus(200);
        $response->assertSee('Frente (5.4 cm x 8.6 cm)');
        $response->assertSee('Reverso (5.4 cm x 8.6 cm)');
        $response->assertSee('Firma y Sello del Director(a)');
    }

    public function test_admin_can_view_teacher_credential_and_scan_teacher_qr_uuid(): void
    {
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 9, 4, 7, 30, 0, 'America/Mexico_City'));

        $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'is_approved' => true,
            'uuid' => 'docente-uuid-5678',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.teachers.credential', $teacher));
        $response->assertStatus(200);
        $response->assertSee('Credencial Docente');
        $response->assertSee('docente-uuid-5678');
        $response->assertSee('Firma y Sello del Director(a)');

        // Test scanning by teacher UUID at Kiosk
        $scanResponse = $this->actingAs($admin)->postJson(route('scan.process'), [
            'qr_code' => 'docente-uuid-5678',
            'scan_method' => 'qr_camera',
        ]);

        $scanResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('student.group', 'Personal Docente');

        $this->assertDatabaseHas('asistencias_docentes', [
            'docente_id' => $teacher->id,
        ]);

        \Carbon\Carbon::setTestNow();
    }
}
