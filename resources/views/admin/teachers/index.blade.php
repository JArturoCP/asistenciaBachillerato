<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-journal-text text-warning me-2"></i> Gestión de Plantilla Docente
            </h2>
            <div>
                <button class="btn btn-warning btn-sm fw-semibold me-2" data-bs-toggle="modal" data-bs-target="#modalAssignGroup">
                    <i class="bi bi-link-45deg me-1"></i> Asignar Materia a Grupo
                </button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateTeacher">
                    <i class="bi bi-person-plus me-1"></i> Nuevo Docente
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Teachers List -->
    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Docente</th>
                        <th>Correo Electrónico</th>
                        <th>Teléfono</th>
                        <th>Materias y Grupos Asignados</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $teacher)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $teacher->nombre_completo }}</div>
                                <span class="badge bg-info text-white">DOCENTE</span>
                            </td>
                            <td>{{ $teacher->email }}</td>
                            <td>{{ $teacher->phone ?? 'Sin registro' }}</td>
                            <td>
                                @forelse($teacher->docenteGrupos as $assignment)
                                    <div class="d-inline-block border rounded p-1 px-2 mb-1 bg-light me-1">
                                        <strong class="text-primary">{{ $assignment->materia }}</strong> 
                                        <span class="badge bg-secondary ms-1">{{ $assignment->grupo->codigo_grupo }}</span>
                                        <form action="{{ route('admin.teachers.removeAssignment', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desasignar esta materia?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0 ms-1" style="font-size: 0.8rem;">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <span class="text-muted small">Sin grupos asignados actualmente</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Activo</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-person-workspace fs-3 d-block mb-2"></i> No hay profesores registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create Teacher -->
    <div class="modal fade" id="modalCreateTeacher" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.teachers.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Registrar Nuevo Docente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre Completo (Nombre ApellidoPaterno ApellidoMaterno)</label>
                            <input type="text" name="name" class="form-control" placeholder="Ej. Roberto Martínez Sánchez" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Correo Electrónico (Login)</label>
                            <input type="email" name="email" class="form-control" placeholder="ejemplo@escuela.edu.mx" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" name="phone" class="form-control" placeholder="10 dígitos">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña Inicial</label>
                            <input type="password" name="password" class="form-control" value="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Crear Cuenta Docente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Assign Group -->
    <div class="modal fade" id="modalAssignGroup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.teachers.assignGroup') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-link-45deg text-warning me-2"></i> Asignar Materia a Grupo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Seleccionar Docente</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->nombre_completo }} ({{ $teacher->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Seleccionar Grupo</label>
                            <select name="group_id" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">Grupo {{ $group->codigo_grupo }} (Turno {{ ucfirst($group->turno) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre de la Asignatura / Materia</label>
                            <input type="text" name="subject_name" class="form-control" placeholder="Ej. Matemáticas I, Física, Historia" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning btn-sm fw-semibold">Guardar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
