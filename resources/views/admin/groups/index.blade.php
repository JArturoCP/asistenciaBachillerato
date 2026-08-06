<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-diagram-3 text-success me-2"></i> Gestión de Grupos Académicos
            </h2>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateGroup">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Grupo
            </button>
        </div>
    </x-slot>

    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código del Grupo</th>
                        <th>Grado / Semestre</th>
                        <th>Turno</th>
                        <th>Ciclo Escolar</th>
                        <th>Estudiantes</th>
                        <th>Docentes Asignados</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="fw-bold text-dark">{{ $group->codigo_grupo }}</span></td>
                            <td>{{ $group->grado }}° Bachillerato</td>
                            <td>
                                <span class="badge {{ $group->turno === 'matutino' ? 'bg-warning text-dark' : 'bg-info text-white' }}">
                                    {{ ucfirst($group->turno) }}
                                </span>
                            </td>
                            <td>{{ $group->ciclo_escolar }}</td>
                            <td>
                                <span class="badge bg-secondary rounded-pill">{{ $group->estudiantes_count }} alumnos</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $group->docente_grupos_count }} asignaciones</span>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este grupo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i> No hay grupos registrados aún.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create Group -->
    <div class="modal fade" id="modalCreateGroup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.groups.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3 text-primary me-2"></i> Registrar Nuevo Grupo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Código del Grupo</label>
                            <input type="text" name="group_code" class="form-control" placeholder="Ej. 1A-MAT" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Grado / Semestre</label>
                            <select name="grade" class="form-select" required>
                                <option value="1">1er Grado</option>
                                <option value="2">2do Grado</option>
                                <option value="3">3er Grado</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Turno</label>
                            <select name="shift" class="form-select" required>
                                <option value="matutino">Matutino</option>
                                <option value="vespertino">Vespertino</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ciclo Escolar</label>
                            <input type="text" name="school_year" class="form-control" value="2026-2027" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
