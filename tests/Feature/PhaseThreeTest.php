<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Tutor;
use App\Models\Asistencia;
use App\Mail\AttendanceRecordedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_teacher_can_access_assigned_group_attendance_report()
    {
        $teacher = User::where('role', 'teacher')->first();

        $response = $this->actingAs($teacher)->get(route('teacher.attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('Reporte de Asistencia por Clase / Grupo');
    }

    public function test_teacher_can_override_student_attendance_status()
    {
        $teacher = User::where('role', 'teacher')->first();
        $student = Estudiante::first();

        $response = $this->actingAs($teacher)->post(route('teacher.attendance.update'), [
            'student_id' => $student->id,
            'date' => date('Y-m-d'),
            'status' => 'justificado',
            'notes' => 'Cita Médica en IMSS',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $student->id,
            'estado' => 'justificado',
            'observaciones' => 'Cita Médica en IMSS',
        ]);
    }

    public function test_teacher_can_export_csv_report()
    {
        $teacher = User::where('role', 'teacher')->first();
        $group = Grupo::first();

        $response = $this->actingAs($teacher)->get(route('teacher.attendance.export', [
            'group_id' => $group->id,
            'date' => date('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_parent_can_access_parent_dashboard_and_see_child_attendance()
    {
        $parentUser = User::where('role', 'parent')->first();

        $response = $this->actingAs($parentUser)->get(route('parent.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Portal de Padres y Tutores');
    }

    public function test_parent_can_update_email_alert_preferences()
    {
        $parentUser = User::where('role', 'parent')->first();

        $response = $this->actingAs($parentUser)->post(route('parent.alerts.update'), [
            'email_alerts_enabled' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tutores', [
            'user_id' => $parentUser->id,
            'alertas_correo_activadas' => true,
        ]);
    }

    public function test_parent_can_submit_arco_request()
    {
        $parentUser = User::where('role', 'parent')->first();

        $response = $this->actingAs($parentUser)->post(route('parent.arco.submit'), [
            'request_type' => 'rectificacion',
            'details' => 'Solicito corregir la ortografía del apellido materno.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $parentUser->id,
            'action' => 'ARCO_REQUEST',
        ]);
    }

    public function test_email_notification_sent_on_scan()
    {
        Mail::fake();

        $admin = User::where('role', 'admin')->first();
        $student = Estudiante::first();

        $this->actingAs($admin)->postJson(route('scan.process'), [
            'qr_code' => $student->uuid,
            'scan_method' => 'qr_camera',
        ]);

        Mail::assertSent(AttendanceRecordedMail::class);
    }
}
