<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Asistencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class PhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_access_scan_kiosk_page()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('scan.index'));
        $response->assertStatus(200);
        $response->assertSee('Estación de Registro Escolar');
    }

    public function test_scan_process_valid_student_checkin()
    {
        $admin = User::where('role', 'admin')->first();
        $student = Estudiante::first();

        $response = $this->actingAs($admin)->postJson(route('scan.process'), [
            'qr_code' => $student->uuid,
            'scan_method' => 'qr_camera',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('student.name', $student->nombre_completo);

        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $student->id,
            'metodo_escaneo' => 'qr_camera',
        ]);
    }

    public function test_scan_process_unknown_qr_returns_404()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->postJson(route('scan.process'), [
            'qr_code' => '00000000-0000-0000-0000-000000000000',
            'scan_method' => 'qr_usb',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('title', 'Código no Reconocido');
    }

    public function test_scan_process_prevents_accidental_duplicate_within_5_minutes()
    {
        $admin = User::where('role', 'admin')->first();
        $student = Estudiante::first();

        // First scan (Check-in)
        $this->actingAs($admin)->postJson(route('scan.process'), [
            'qr_code' => $student->uuid,
            'scan_method' => 'qr_usb',
        ]);

        // Second scan immediately (Duplicate check)
        $response = $this->actingAs($admin)->postJson(route('scan.process'), [
            'qr_code' => $student->uuid,
            'scan_method' => 'qr_usb',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('title', 'Escaneo Duplicado Detectado');
    }

    public function test_scan_process_registers_checkout_after_delay()
    {
        $admin = User::where('role', 'admin')->first();
        $student = Estudiante::first();

        // Create check-in attendance 10 minutes ago
        $pastTime = Carbon::now()->subMinutes(10);
        Asistencia::create([
            'estudiante_id' => $student->id,
            'fecha' => Carbon::today()->format('Y-m-d'),
            'hora_entrada' => $pastTime->format('H:i:s'),
            'estado' => 'presente',
            'metodo_escaneo' => 'qr_usb',
        ]);

        // Scan again (Check-out)
        $response = $this->actingAs($admin)->postJson(route('scan.process'), [
            'qr_code' => $student->uuid,
            'scan_method' => 'qr_usb',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('title', 'Salida Registrada');

        $attendance = Asistencia::where('estudiante_id', $student->id)->first();
        $this->assertNotNull($attendance->hora_salida);
    }
}
