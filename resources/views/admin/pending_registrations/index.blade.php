<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-person-check-fill text-warning me-2"></i> Solicitudes de Registro Pendientes
            </h2>
            <span class="badge bg-danger rounded-pill px-3 py-2 fs-6">
                {{ $pendingUsers->count() }} {{ Str::plural('solicitud', $pendingUsers->count()) }} pendientes
            </span>
        </div>
    </x-slot>

    <!-- Info Banner -->
    <div class="alert alert-light border card-custom p-3 mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-lock text-primary fs-3 me-3"></i>
            <div>
                <h6 class="fw-bold mb-1">Control de Seguridad y Acceso Escolar</h6>
                <p class="mb-0 small text-muted">
                    Las solicitudes registradas por usuarios externos requieren ser validadas. Al <strong>Aprobar</strong>, el usuario podrá acceder al sistema según su rol. Al <strong>Rechazar</strong>, el registro se eliminará permanentemente de la base de datos.
                </p>
            </div>
        </div>
    </div>

    <!-- Pending Users Table -->
    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Solicitante</th>
                        <th>Correo Electrónico</th>
                        <th>Rol Solicitado</th>
                        <th>Teléfono</th>
                        <th>Fecha de Solicitud</th>
                        <th class="text-end">Acciones de Aprobación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingUsers as $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold text-dark fs-6">{{ $user->nombre_completo }}</div>
                                <small class="text-muted">ID Usuario: #{{ $user->id }}</small>
                            </td>
                            <td>
                                <i class="bi bi-envelope me-1 text-muted"></i> {{ $user->email }}
                            </td>
                            <td>
                                @if($user->isTeacher())
                                    <span class="badge bg-primary text-white">
                                        <i class="bi bi-journal-text me-1"></i> Docente / Profesor
                                    </span>
                                @elseif($user->isParent())
                                    <span class="badge bg-success text-white">
                                        <i class="bi bi-house-heart me-1"></i> Padre de Familia / Tutor
                                    </span>
                                @else
                                    <span class="badge bg-secondary">{{ strtoupper($user->role) }}</span>
                                @endif
                            </td>
                            <td>
                                <i class="bi bi-telephone me-1 text-muted"></i> {{ $user->phone ?? 'Sin teléfono' }}
                            </td>
                            <td>
                                <small class="text-dark">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i A') : 'N/A' }}</small>
                            </td>
                            <td class="text-end">
                                <!-- Approve Form -->
                                <form action="{{ route('admin.pending-registrations.approve', $user) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm me-1 font-semibold" title="Aprobar Solicitud">
                                        <i class="bi bi-check-circle-fill me-1"></i> Aprobar Acceso
                                    </button>
                                </form>

                                <!-- Reject Form -->
                                <form action="{{ route('admin.pending-registrations.reject', $user) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event, '¿Está seguro de rechazar la solicitud de {{ $user->nombre_completo }}? Esta acción eliminará permanentemente el registro.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Rechazar y Eliminar">
                                        <i class="bi bi-x-circle-fill me-1"></i> Rechazar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-all text-success display-4 d-block mb-2"></i>
                                <h5 class="fw-bold">No hay solicitudes pendientes</h5>
                                <p class="small mb-0">Todas las solicitudes de registro han sido procesadas correctamente.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
