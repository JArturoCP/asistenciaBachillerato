<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-person-badge text-primary me-2"></i> Gestión de Estudiantes
            </h2>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateStudent">
                <i class="bi bi-person-plus me-1"></i> Registrar Estudiante
            </button>
        </div>
    </x-slot>

    <!-- Filter Bar -->
    <div class="card card-custom p-3 bg-white mb-4">
        <form method="GET" action="{{ route('admin.students.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o matrícula..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="group_id" class="form-select form-select-sm">
                    <option value="">-- Todos los Grupos --</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                            Grupo {{ $group->codigo_grupo }} ({{ ucfirst($group->turno) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark btn-sm w-100"><i class="bi bi-funnel"></i> Filtrar</button>
                @if(request()->hasAny(['search', 'group_id']))
                    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i> Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Matrícula</th>
                        <th>Nombre Completo</th>
                        <th>Grupo</th>
                        <th>UUID QR (Seudónimo)</th>
                        <th>Tutor(es) Asignado(s)</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones / Credencial</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>
                                @if($student->foto)
                                    <img src="{{ asset('storage/' . $student->foto) }}" alt="Foto de {{ $student->nombre_completo }}" class="rounded-circle border object-fit-cover" style="width: 42px; height: 42px;">
                                @else
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold" style="width: 42px; height: 42px;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                @endif
                            </td>
                            <td><span class="fw-bold font-monospace text-dark">{{ $student->matricula }}</span></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $student->nombre_completo }}</div>
                                <small class="text-muted">Nacimiento: {{ $student->fecha_nacimiento ? $student->fecha_nacimiento->format('d/m/Y') : 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-2 py-1">
                                    {{ $student->grupo->codigo_grupo }}
                                </span>
                            </td>
                            <td>
                                <code class="small text-muted" title="{{ $student->uuid }}">
                                    {{ Str::limit($student->uuid, 16) }}
                                </code>
                            </td>
                            <td>
                                @forelse($student->tutores as $guardian)
                                    <span class="badge bg-light text-dark border me-1">
                                        <i class="bi bi-shield-check text-success me-1"></i> {{ $guardian->user->nombre_completo }}
                                    </span>
                                @empty
                                    <span class="badge bg-warning text-dark">Sin tutor vinculado</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="badge {{ $student->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $student->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.students.credential', $student) }}" target="_blank" class="btn btn-outline-dark btn-sm me-1" title="Ver e imprimir Credencial QR">
                                    <i class="bi bi-qr-code text-primary"></i> Credencial
                                </a>
                                <button class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEditStudent-{{ $student->id }}" title="Editar Estudiante">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event, '¿Está seguro de eliminar al estudiante {{ $student->nombre_completo }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar Estudiante">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                                <!-- Modal Edit Student -->
                                <div class="modal fade text-start" id="modalEditStudent-{{ $student->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content card-custom">
                                            <form action="{{ route('admin.students.update', $student) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="form_modal_id" value="modalEditStudent-{{ $student->id }}">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Editar Estudiante</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @if($errors->any() && old('form_modal_id') === 'modalEditStudent-' . $student->id)
                                                        <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Verifique los siguientes errores:</strong>
                                                            <ul class="mb-0 mt-1 ps-3">
                                                                @foreach($errors->all() as $error)
                                                                    <li>{{ $error }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Matrícula</label>
                                                            <input type="text" name="matricula" class="form-control @error('matricula') is-invalid @enderror" value="{{ old('matricula', $student->matricula) }}" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Fotografía del Alumno (Opcional)</label>
                                                            <input type="file" name="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/*">
                                                            @if($student->foto)
                                                                <small class="text-success"><i class="bi bi-check-circle me-1"></i> Fotografía guardada previamente</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Nombre(s)</label>
                                                            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $student->user->nombre) }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Apellido Paterno</label>
                                                            <input type="text" name="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno', $student->user->apellido_paterno) }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Apellido Materno</label>
                                                            <input type="text" name="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno', $student->user->apellido_materno) }}">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Fecha de Nacimiento</label>
                                                        <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', $student->fecha_nacimiento ? $student->fecha_nacimiento->format('Y-m-d') : '') }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Grupo Asignado</label>
                                                        <select name="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                                                            @foreach($groups as $group)
                                                                <option value="{{ $group->id }}" {{ old('group_id', $student->grupo_id) == $group->id ? 'selected' : '' }}>
                                                                    Grupo {{ $group->codigo_grupo }} (Turno {{ ucfirst($group->turno) }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Estado del Estudiante</label>
                                                        <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                                                            <option value="1" {{ old('is_active', $student->is_active) ? 'selected' : '' }}>Activo</option>
                                                            <option value="0" {{ !old('is_active', $student->is_active) ? 'selected' : '' }}>Inactivo</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary btn-sm">Actualizar Estudiante</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-person-x fs-3 d-block mb-2"></i> No se encontraron estudiantes con los criterios especificados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $students->withQueryString()->links() }}
        </div>
    </div>

    <!-- Modal Create Student -->
    <div class="modal fade" id="modalCreateStudent" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.students.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="form_modal_id" value="modalCreateStudent">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Registrar Nuevo Estudiante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($errors->any() && old('form_modal_id') === 'modalCreateStudent')
                            <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Verifique los siguientes errores:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Matrícula Asignada (Automática)</label>
                                <input type="text" name="matricula" class="form-control bg-light fw-bold text-primary font-monospace" value="{{ $nextMatricula }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Fotografía del Alumno (Opcional)</label>
                                <input type="file" name="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/*">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Nombre(s)</label>
                                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" placeholder="Ej. Juan Manuel" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Apellido Paterno</label>
                                <input type="text" name="apellido_paterno" class="form-control @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno') }}" placeholder="Ej. Pérez" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Apellido Materno</label>
                                <input type="text" name="apellido_materno" class="form-control @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno') }}" placeholder="Ej. Gómez">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Fecha de Nacimiento</label>
                            <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Grupo Asignado</label>
                            <select name="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                                <option value="">-- Seleccionar Grupo --</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>Grupo {{ $group->codigo_grupo }} (Turno {{ ucfirst($group->turno) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle-fill me-1"></i> Se generará automáticamente un <strong>UUID seudónimo</strong> para el código QR de la credencial en cumplimiento con la LFPDPPP.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar Estudiante</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
