<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tutor;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminPendingRegistrationController extends Controller
{
    public function index()
    {
        $pendingUsers = User::pending()->orderBy('created_at', 'desc')->get();

        AuditLog::log('READ', 'users', null, 'Consulta de solicitudes de registro pendientes');

        return view('admin.pending_registrations.index', compact('pendingUsers'));
    }

    public function approve(User $user)
    {
        $user->update([
            'is_approved' => true,
        ]);

        // If parent and missing tutor profile, ensure Tutor record exists
        if ($user->isParent() && !$user->tutor) {
            Tutor::create([
                'user_id' => $user->id,
                'parentesco' => 'padre',
                'telefono' => $user->phone,
                'correo_notificaciones' => $user->email,
                'alertas_correo_activadas' => true,
            ]);
        }

        AuditLog::log('WRITE', 'users', $user->id, "Solicitud de registro APROBADA para: {$user->nombre_completo}");

        return redirect()->route('admin.pending-registrations.index')
            ->with('success', "La solicitud de registro para {$user->nombre_completo} ha sido APROBADA. El usuario ya puede iniciar sesión.");
    }

    public function reject(User $user)
    {
        $name = $user->nombre_completo;

        if ($user->tutor) {
            $user->tutor->delete();
        }

        $user->delete();

        AuditLog::log('WRITE', 'users', null, "Solicitud de registro RECHAZADA (Soft Delete) para: {$name}");

        return redirect()->route('admin.pending-registrations.index')
            ->with('success', "La solicitud de registro para {$name} ha sido rechazada.");
    }
}
