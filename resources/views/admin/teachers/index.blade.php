<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-journal-text text-warning me-2"></i> Plantilla Docente y Carga Académica (Bachillerato)
            </h2>
            <div>
                <button class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalCreateMateria">
                    <i class="bi bi-book me-1"></i> Catálogo de Materias
                </button>
                <button class="btn btn-warning btn-sm fw-semibold me-2" data-bs-toggle="modal" data-bs-target="#modalAssignGroup">
                    <i class="bi bi-calendar-event me-1"></i> Asignar Clase / Horario
                </button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateTeacher">
                    <i class="bi bi-person-plus me-1"></i> Nuevo Docente
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Teachers List -->
    <div class="card card-custom p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-person-workspace text-primary me-2"></i> Carga Horaria por Docente</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Docente</th>
                        <th>Contacto</th>
                        <th>Carga Horaria / Clases Impartidas</th>
                        <th class="text-end">Acciones Docente</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $teacher)
                        <tr>
                            <td style="width: 25%;">
                                <div class="fw-bold text-dark fs-6">{{ $teacher->nombre_completo }}</div>
                                <span class="badge bg-info text-white">DOCENTE BACHILLERATO</span>
                            </td>
                            <td style="width: 20%;">
                                <div class="small"><i class="bi bi-envelope text-muted me-1"></i> {{ $teacher->email }}</div>
                                <div class="small text-muted"><i class="bi bi-telephone text-muted me-1"></i> {{ $teacher->phone ?? 'Sin registro' }}</div>
                            </td>
                            <td>
                                @forelse($teacher->docenteGrupos as $assignment)
                                    <div class="d-inline-block border rounded p-2 mb-2 bg-light me-2 align-top shadow-sm position-relative">
                                        <div class="fw-bold text-primary">
                                            <i class="bi bi-book-half me-1"></i> {{ $assignment->materia?->nombre ?? 'Asignatura' }}
                                        </div>
                                        <div class="small text-dark">
                                            <span class="badge bg-secondary me-1">Grupo {{ $assignment->grupo->codigo_grupo }}</span>
                                            <span class="badge bg-dark">{{ ucfirst($assignment->dia_semana) }}</span>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="bi bi-clock me-1"></i> {{ substr($assignment->hora_inicio, 0, 5) }} - {{ substr($assignment->hora_fin, 0, 5) }}
                                            @if($assignment->aula)
                                                | <i class="bi bi-geo-alt me-1"></i> {{ $assignment->aula }}
                                            @endif
                                        </div>
                                        <div class="mt-2 d-flex gap-2 border-top pt-1">
                                            <button class="btn btn-link text-primary p-0 small text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalEditAssignment-{{ $assignment->id }}" title="Editar Horario">
                                                <i class="bi bi-pencil me-1"></i> Editar Horario
                                            </button>
                                            <form action="{{ route('admin.teachers.removeAssignment', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desasignar este horario/materia al docente?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger p-0 small text-decoration-none">
                                                    <i class="bi bi-trash me-1"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Modal Edit Assignment -->
                                        <div class="modal fade text-start" id="modalEditAssignment-{{ $assignment->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content card-custom">
                                                    <form action="{{ route('admin.teachers.updateAssignment', $assignment) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Editar Horario / Clase</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Asignatura / Materia</label>
                                                                <select name="materia_id" class="form-select" required>
                                                                    @foreach($materias as $materia)
                                                                        <option value="{{ $materia->id }}" {{ $assignment->materia_id == $materia->id ? 'selected' : '' }}>
                                                                            {{ $materia->nombre }} ({{ $materia->clave }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Grupo Alumno</label>
                                                                <select name="group_id" class="form-select" required>
                                                                    @foreach($groups as $group)
                                                                        <option value="{{ $group->id }}" {{ $assignment->grupo_id == $group->id ? 'selected' : '' }}>
                                                                            Grupo {{ $group->codigo_grupo }} (Turno {{ ucfirst($group->turno) }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Día de la Semana</label>
                                                                <select name="dia_semana" class="form-select" required>
                                                                    <option value="lunes" {{ $assignment->dia_semana == 'lunes' ? 'selected' : '' }}>Lunes</option>
                                                                    <option value="martes" {{ $assignment->dia_semana == 'martes' ? 'selected' : '' }}>Martes</option>
                                                                    <option value="miercoles" {{ $assignment->dia_semana == 'miercoles' ? 'selected' : '' }}>Miércoles</option>
                                                                    <option value="jueves" {{ $assignment->dia_semana == 'jueves' ? 'selected' : '' }}>Jueves</option>
                                                                    <option value="viernes" {{ $assignment->dia_semana == 'viernes' ? 'selected' : '' }}>Viernes</option>
                                                                    <option value="sabado" {{ $assignment->dia_semana == 'sabado' ? 'selected' : '' }}>Sábado</option>
                                                                </select>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-semibold">Hora Inicio</label>
                                                                    <input type="time" name="start_time" class="form-control" value="{{ substr($assignment->hora_inicio, 0, 5) }}" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-semibold">Hora Fin</label>
                                                                    <input type="time" name="end_time" class="form-control" value="{{ substr($assignment->hora_fin, 0, 5) }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Aula / Salón (Opcional)</label>
                                                                <input type="text" name="aula" class="form-control" value="{{ $assignment->aula }}">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-warning btn-sm fw-semibold">Actualizar Horario</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-muted small">Sin materias ni clases asignadas aún.</span>
                                @endforelse
                            </td>
                            <td class="text-end" style="width: 15%;">
                                <button class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEditTeacher-{{ $teacher->id }}" title="Editar Docente">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('admin.teachers.destroyTeacher', $teacher) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar a este Docente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar Docente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                                <!-- Modal Edit Teacher -->
                                <div class="modal fade text-start" id="modalEditTeacher-{{ $teacher->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content card-custom">
                                            <form action="{{ route('admin.teachers.updateTeacher', $teacher) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Editar Datos de Docente</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Nombre(s)</label>
                                                            <input type="text" name="nombre" class="form-control" value="{{ $teacher->nombre }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Apellido Paterno</label>
                                                            <input type="text" name="apellido_paterno" class="form-control" value="{{ $teacher->apellido_paterno }}" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label fw-semibold">Apellido Materno</label>
                                                            <input type="text" name="apellido_materno" class="form-control" value="{{ $teacher->apellido_materno }}">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Correo Electrónico (Login)</label>
                                                        <input type="email" name="email" class="form-control" value="{{ $teacher->email }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Teléfono</label>
                                                        <input type="text" name="phone" class="form-control" value="{{ $teacher->phone }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Nueva Contraseña (Opcional)</label>
                                                        <input type="password" name="password" class="form-control" placeholder="Dejar en blanco para conservar actual">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary btn-sm">Actualizar Docente</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.teachers.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Registrar Nuevo Docente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Nombre(s)</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej. Roberto" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Apellido Paterno</label>
                                <input type="text" name="apellido_paterno" class="form-control" placeholder="Ej. Martínez" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Apellido Materno</label>
                                <input type="text" name="apellido_materno" class="form-control" placeholder="Ej. Sánchez">
                            </div>
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

    <!-- Modal Create Materia -->
    <div class="modal fade" id="modalCreateMateria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.teachers.storeMateria') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-book text-primary me-2"></i> Registrar Nueva Asignatura</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Clave de la Materia</label>
                            <input type="text" name="clave" class="form-control" placeholder="Ej. MAT-101" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre de la Asignatura</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej. Matemáticas I, Física II" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Semestre / Grado</label>
                            <select name="semestre" class="form-select" required>
                                <option value="1">1er Semestre</option>
                                <option value="2">2do Semestre</option>
                                <option value="3">3er Semestre</option>
                                <option value="4">4to Semestre</option>
                                <option value="5">5to Semestre</option>
                                <option value="6">6to Semestre</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar Asignatura</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Assign Schedule / Class -->
    <div class="modal fade" id="modalAssignGroup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card-custom">
                <form action="{{ route('admin.teachers.assignGroup') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-calendar-event text-warning me-2"></i> Asignar Clase y Horario a Docente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Docente</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Seleccionar Docente --</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->nombre_completo }} ({{ $teacher->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Asignatura / Materia</label>
                            <select name="materia_id" class="form-select" required>
                                <option value="">-- Seleccionar Materia --</option>
                                @foreach($materias as $materia)
                                    <option value="{{ $materia->id }}">{{ $materia->nombre }} ({{ $materia->clave }} - {{ $materia->semestre }}° Semestre)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Grupo Alumno</label>
                            <select name="group_id" class="form-select" required>
                                <option value="">-- Seleccionar Grupo --</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">Grupo {{ $group->codigo_grupo }} (Turno {{ ucfirst($group->turno) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Día de la Semana</label>
                            <select name="dia_semana" class="form-select" required>
                                <option value="lunes">Lunes</option>
                                <option value="martes">Martes</option>
                                <option value="miercoles">Miércoles</option>
                                <option value="jueves">Jueves</option>
                                <option value="viernes">Viernes</option>
                                <option value="sabado">Sábado</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hora Inicio</label>
                                <input type="time" name="start_time" class="form-control" value="07:00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hora Fin</label>
                                <input type="time" name="end_time" class="form-control" value="08:40" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Aula / Salón (Opcional)</label>
                            <input type="text" name="aula" class="form-control" placeholder="Ej. Aula 102, Lab. Física">
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
