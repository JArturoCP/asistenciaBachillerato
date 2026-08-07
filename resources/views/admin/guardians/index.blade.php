<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-shield-check text-info me-2"></i> Padres de Familia y Consentimiento LFPDPPP
            </h2>
            <div>
                <button class="btn btn-info text-white btn-sm fw-semibold me-2" data-bs-toggle="modal" data-bs-target="#modalLinkStudent">
                    <i class="bi bi-link-45deg me-1"></i> Vinculación Alumno & Consentimiento
                </button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateGuardian">
                    <i class="bi bi-person-plus me-1"></i> Nuevo Padre/Tutor
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Info Banner LFPDPPP -->
    <div class="alert alert-light border card-custom p-3 mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-lock-fill text-success fs-3 me-3"></i>
            <div>
                <h6 class="fw-bold mb-1">Cumplimiento Normativo LFPDPPP (Escuela Privada México)</h6>
                <p class="mb-0 small text-muted">
                    Al ser los estudiantes en su mayoría menores de edad, el tratamiento de sus datos personales requiere el consentimiento expreso e informado del padre o tutor legal mediante el Aviso de Privacidad.
                </p>
            </div>
        </div>
    </div>

    <!-- Guardians Table -->
    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Tutor / Representante</th>
                        <th>Parentesco</th>
                        <th>Correo de Alertas</th>
                        <th>Teléfono</th>
                        <th>Hijo(s) Representado(s)</th>
                        <th>Estado Consentimiento LFPDPPP</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guardians as $guardian)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $guardian->user->nombre_completo }}</div>
                                <small class="text-muted">{{ $guardian->user->email }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ ucfirst($guardian->parentesco) }}</span>
                            </td>
                            <td>
                                <i class="bi bi-envelope text-primary me-1"></i> {{ $guardian->correo_notificaciones }}
                            </td>
                            <td>{{ $guardian->telefono ?? 'Sin registro' }}</td>
                            <td>
                                @forelse($guardian->estudiantes as $student)
                                    <div class="badge bg-light text-dark border p-2 me-1 mb-1">
                                        <i class="bi bi-mortarboard-fill text-primary me-1"></i>
                                        <strong>{{ $student->nombre_completo }}</strong> ({{ $student->grupo->codigo_grupo }})
                                    </div>
                                @empty
                                    <span class="badge bg-warning text-dark">Sin alumnos vinculados</span>
                                @endforelse
                            </td>
                            <td>
                                @php
                                    $hasConsent = $guardian->consentimientos->where('aceptado', true)->first();
                                @endphp
                                @if($hasConsent)
                                    <span class="badge bg-success p-2">
                                        <i class="bi bi-shield-check me-1"></i> Aceptado ({{ $hasConsent->fecha_otorgado ? $hasConsent->fecha_otorgado->format('d/m/Y') : 'Firmado' }})
                                    </span>
                                @else
                                    <span class="badge bg-danger p-2">
                                        <i class="bi bi-exclamation-octagon me-1"></i> Pendiente
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEditGuardian-{{ $guardian->id }}" title="Editar Tutor">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('admin.guardians.destroyGuardian', $guardian) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar a este Padre/Tutor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar Tutor">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                                <!-- Modal Edit Guardian -->
                                <div class="modal fade text-start" id="modalEditGuardian-{{ $guardian->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content card-custom">
                                            <form action="{{ route('admin.guardians.updateGuardian', $guardian) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Editar Padre / Tutor</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Nombre(s)</label>
                                                            <input type="text" name="nombre" class="form-control" value="{{ $guardian->user->nombre }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Apellido Paterno</label>
                                                            <input type="text" name="apellido_paterno" class="form-control" value="{{ $guardian->user->apellido_paterno }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Apellido Materno</label>
                                                            <input type="text" name="apellido_materno" class="form-control" value="{{ $guardian->user->apellido_materno }}">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Correo Electrónico (Acceso Portal y Alertas)</label>
                                                        <input type="email" name="email" class="form-control" value="{{ $guardian->user->email }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Parentesco con el Estudiante</label>
                                                        <select name="relationship" class="form-select" required>
                                                            <option value="padre" {{ $guardian->parentesco == 'padre' ? 'selected' : '' }}>Padre</option>
                                                            <option value="madre" {{ $guardian->parentesco == 'madre' ? 'selected' : '' }}>Madre</option>
                                                            <option value="tutor_legal" {{ $guardian->parentesco == 'tutor_legal' ? 'selected' : '' }}>Tutor Legal</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Teléfono Celular</label>
                                                        <input type="text" name="phone" class="form-control" value="{{ $guardian->telefono }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Nueva Contraseña (Opcional)</label>
                                                        <input type="password" name="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary btn-sm">Actualizar Tutor</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-shield-x fs-3 d-block mb-2"></i> No se han registrado padres o tutores aún.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create Guardian -->
    <div class="modal fade" id="modalCreateGuardian" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.guardians.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Registrar Padre / Tutor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Nombre(s)</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej. Carlos" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Apellido Paterno</label>
                                <input type="text" name="apellido_paterno" class="form-control" placeholder="Ej. Pérez" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Apellido Materno</label>
                                <input type="text" name="apellido_materno" class="form-control" placeholder="Ej. Hernández">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Correo Electrónico (Acceso Portal y Alertas)</label>
                            <input type="email" name="email" class="form-control" placeholder="tutor@gmail.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Parentesco con el Estudiante</label>
                            <select name="relationship" class="form-select" required>
                                <option value="padre">Padre</option>
                                <option value="madre">Madre</option>
                                <option value="tutor_legal">Tutor Legal</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Teléfono Celular</label>
                            <input type="text" name="phone" class="form-control" placeholder="10 dígitos">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña Inicial</label>
                            <input type="password" name="password" class="form-control" value="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar Tutor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Link Student & Consent -->
    <div class="modal fade" id="modalLinkStudent" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.guardians.linkStudent') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-shield-check text-info me-2"></i> Vinculación & Registro de Consentimiento LFPDPPP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Padre / Tutor</label>
                                <select name="guardian_id" class="form-select" required>
                                    <option value="">-- Seleccionar Tutor --</option>
                                    @foreach($guardians as $guardian)
                                        <option value="{{ $guardian->id }}">{{ $guardian->user->nombre_completo }} ({{ $guardian->user->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Estudiante Representado</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- Seleccionar Alumno --</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->nombre_completo }} ({{ $student->grupo->codigo_grupo }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de Contacto</label>
                            <select name="is_primary_contact" class="form-select" required>
                                <option value="1">Contacto Principal de Emergencia/Notificación</option>
                                <option value="0">Contacto Secundario</option>
                            </select>
                        </div>

                        <div class="p-3 bg-light border rounded">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="consent_accepted" id="consentCheck" value="1" required>
                                <label class="form-check-label fw-semibold text-dark" for="consentCheck">
                                    El tutor otorga su consentimiento expreso e informado para el tratamiento de datos personales de su representado menor de edad conforme al <a href="#" onclick="alert('Aviso de Privacidad conforme a la LFPDPPP registrado.'); return false;">Aviso de Privacidad LFPDPPP</a> de la institución.
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info text-white btn-sm fw-semibold">Registrar Vinculación y Firma</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
