@extends('layouts.app')

@section('header')
    <h2 class="h4 font-weight-bold text-dark mb-0">
        <i class="bi bi-speedometer2 text-primary me-2"></i> Panel de Control Principal
    </h2>
@endsection

@section('content')

    @php
        $totalStudents = \App\Models\Estudiante::count();
        $totalGroups = \App\Models\Grupo::count();
        $totalTeachers = \App\Models\User::whereIn('role', ['teacher', 'docente'])->count();
        $totalGuardians = \App\Models\Tutor::count();
        $totalConsents = \App\Models\Consentimiento::where('aceptado', true)->count();
    @endphp

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-primary border-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold">Estudiantes Registrados</div>
                        <div class="fs-3 fw-bold">{{ $totalStudents }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-success border-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                        <i class="bi bi-diagram-3-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold">Grupos Académicos</div>
                        <div class="fs-3 fw-bold">{{ $totalGroups }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-warning border-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                        <i class="bi bi-journal-check fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold">Plantilla Docente</div>
                        <div class="fs-3 fw-bold">{{ $totalTeachers }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-info border-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold">Consentimientos LFPDPPP</div>
                        <div class="fs-3 fw-bold">{{ $totalConsents }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Section -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card card-custom p-4 bg-white">
                <h5 class="fw-bold mb-3"><i class="bi bi-grid-fill text-primary me-2"></i> Módulos de Administración (Fase 1)</h5>
                <p class="text-muted small mb-4">Gestión integral de usuarios, grupos, credenciales estudiantiles con QR seudónimos y privacidad LFPDPPP.</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('admin.students.index') }}" class="card text-decoration-none card-custom h-100 p-3 border hover-shadow transition">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded p-3 me-3">
                                    <i class="bi bi-person-badge fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Gestión de Estudiantes</h6>
                                    <small class="text-muted">Altas, bajas, asignación de grupo y credencial QR.</small>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="{{ route('admin.groups.index') }}" class="card text-decoration-none card-custom h-100 p-3 border hover-shadow transition">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded p-3 me-3">
                                    <i class="bi bi-diagram-3 fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Grupos y Turnos</h6>
                                    <small class="text-muted">Configuración de grupos (ej. 1A-MAT, 2B-VESP).</small>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="{{ route('admin.teachers.index') }}" class="card text-decoration-none card-custom h-100 p-3 border hover-shadow transition">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning text-dark rounded p-3 me-3">
                                    <i class="bi bi-person-workspace fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Docentes y Asignaciones</h6>
                                    <small class="text-muted">Cuentas de profesores y asignación a grupos.</small>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="{{ route('admin.guardians.index') }}" class="card text-decoration-none card-custom h-100 p-3 border hover-shadow transition">
                            <div class="d-flex align-items-center">
                                <div class="bg-info text-white rounded p-3 me-3">
                                    <i class="bi bi-shield-check fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Padres y Consentimientos</h6>
                                    <small class="text-muted">Vinculación de tutores y aviso de privacidad LFPDPPP.</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white">
                <h5 class="fw-bold mb-3"><i class="bi bi-shield-shaded text-success me-2"></i> Privacy by Design (LFPDPPP)</h5>
                <div class="alert alert-light border small text-secondary mb-3">
                    <i class="bi bi-lock-fill text-warning me-1"></i>
                    <strong>QR Seudonomizado Activo</strong>: Los códigos QR impresos contienen únicamente un UUID aleatorio cifrado. Ninguna CURP o nombre es expuesto visualmente en los códigos QR.
                </div>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <span>Aviso Privacidad Menores</span>
                        <span class="badge bg-success rounded-pill">Configurado</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <span>Consentimiento Parental</span>
                        <span class="badge bg-primary rounded-pill">Obligatorio</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <span>Bitácora de Auditoría</span>
                        <span class="badge bg-info rounded-pill">Activa</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
