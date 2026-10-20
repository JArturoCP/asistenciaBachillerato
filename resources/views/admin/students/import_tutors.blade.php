@extends('layouts.app')

@section('header')
<div class="d-flex align-items-center justify-content-between">
    <h2 class="h4 mb-0">
        <i class="bi bi-people-fill text-primary me-2"></i> Vincular tutores desde CSV
    </h2>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.students.index') }}">
        <i class="bi bi-arrow-left me-1"></i> Volver a estudiantes
    </a>
</div>
@endsection

@section('content')
<div class="card card-custom bg-white p-4 mb-4">
    <div class="alert alert-primary mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-shield-check me-1"></i> Este proceso NO modifica alumnos</div>
        <div class="small">
            El archivo se utiliza únicamente para localizar estudiantes ya registrados, crear o reutilizar tutores y generar la relación estudiante-tutor.
            No cambia nombre, matrícula, grupo, fecha de nacimiento, correo, teléfono, estado ni UUID del alumno.
        </div>
    </div>

    <h5>1. Archivo CSV</h5>
    <p class="text-muted mb-3">
        Puede utilizar el archivo que originalmente contenía estudiantes y tutores. Máximo 2 MB y 2,000 filas por carga.
    </p>

    <div class="row g-3 mb-3 small">
        <div class="col-lg-6">
            <div class="border rounded p-3 h-100">
                <div class="fw-bold mb-2">¿Cómo se localiza al estudiante?</div>
                <ol class="mb-0 ps-3">
                    <li>Si <code>matricula</code> tiene valor, se utiliza como identificador principal.</li>
                    <li>Si está vacía, se busca por <strong>nombre completo + fecha de nacimiento + grupo</strong>.</li>
                    <li>Si no existe o hay ambigüedad, la carga se detiene; nunca se crea un alumno nuevo.</li>
                </ol>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded p-3 h-100">
                <div class="fw-bold mb-2">¿Cómo se localiza al tutor?</div>
                <ol class="mb-0 ps-3">
                    <li>Primero por <strong>correo electrónico</strong>.</li>
                    <li>Si no hay coincidencia, por <strong>teléfono + nombre completo</strong>.</li>
                    <li>Si ya existe, sólo se relaciona. Sus datos actuales no se sobrescriben.</li>
                    <li>Si existe con eliminación suave, se <strong>restaura el mismo tutor</strong> y luego se relaciona.</li>
                    <li>Si la relación alumno-tutor ya existe, se <strong>ignora</strong>; no se duplica ni se modifica.</li>
                    <li>Si no existe, se crea y posteriormente se vincula.</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="alert alert-warning small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        La vinculación no equivale al consentimiento para notificaciones. Los tutores nuevos se crean con alertas desactivadas y
        <code>fecha_verificacion</code> pendiente. Si el vínculo ya existe, no se modifica su fecha de verificación.
    </div>

    <a href="{{ route('admin.students.tutors-import.template') }}" class="btn btn-outline-success btn-sm mb-3">
        <i class="bi bi-download me-1"></i> Descargar plantilla
    </a>

    <form action="{{ route('admin.students.tutors-import.preview') }}" enctype="multipart/form-data" method="POST">
        @csrf
        <label for="archivo" class="form-label fw-bold">2. Seleccionar CSV con tutores</label>
        <input id="archivo" name="archivo" type="file" class="form-control mb-3" accept=".csv,text/csv" required>
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-search me-1"></i> Validar y localizar registros
        </button>
    </form>
</div>

@if($preview)
<div class="card card-custom bg-white p-4">
    <h5>Vista previa de vinculación</h5>

    <div class="row g-2 mb-3 small">
        <div class="col-md-3"><div class="border rounded p-2"><strong>Filas:</strong> {{ $preview['count'] }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>Alumnos localizados:</strong> {{ $preview['students_matched'] }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>Tutores nuevos:</strong> {{ $preview['tutors_new'] }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>Tutores existentes:</strong> {{ $preview['tutors_existing'] }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>A restaurar:</strong> {{ $preview['tutors_to_restore'] ?? 0 }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>Vínculos nuevos:</strong> {{ $preview['links_new'] }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>Ya vinculados:</strong> {{ $preview['links_existing'] }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>Advertencias:</strong> {{ $preview['total_warnings'] }}</div></div>
        <div class="col-md-3"><div class="border rounded p-2"><strong>Errores:</strong> {{ $preview['total_errors'] }}</div></div>
    </div>

    @if($preview['warnings'])
        <div class="alert alert-warning">
            <strong>Advertencias:</strong>
            <ul class="mt-2 mb-0">
                @foreach($preview['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            @if($preview['total_warnings'] > count($preview['warnings']))
                <small>Se muestran las primeras 50 advertencias.</small>
            @endif
        </div>
    @endif

    @if($preview['errors'])
        <div class="alert alert-danger">
            <strong>No se aplicó ningún cambio.</strong> Corrija los errores antes de confirmar.
            <ul class="mt-2 mb-0">
                @foreach($preview['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            @if($preview['total_errors'] > count($preview['errors']))
                <small>Se muestran los primeros 50 errores.</small>
            @endif
        </div>
    @elseif(count($preview['rows']))
        <div class="alert alert-success">
            Todos los alumnos fueron localizados sin modificarlos. Revise la relación propuesta antes de confirmar.
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Matrícula BD</th>
                        <th>Estudiante encontrado</th>
                        <th>Grupo BD</th>
                        <th>Tutor</th>
                        <th>Estado tutor</th>
                        <th>Relación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['sample'] as $row)
                    <tr>
                        <td class="font-monospace fw-semibold">{{ $row['matricula'] }}</td>
                        <td>{{ $row['estudiante'] }}</td>
                        <td>{{ $row['grupo'] }}</td>
                        <td>{{ $row['tutor'] }}</td>
                        <td>
                            @php
                                $tutorBadge = str_contains($row['tutor_estado'], 'restaurará')
                                    ? 'bg-warning text-dark'
                                    : ($row['tutor_estado'] === 'Tutor existente' ? 'bg-info text-dark' : 'bg-primary');
                            @endphp
                            <span class="badge {{ $tutorBadge }}">{{ $row['tutor_estado'] }}</span>
                        </td>
                        <td>
                            @php
                                $vinculoBadge = str_starts_with($row['vinculo_estado'], 'Ya vinculado')
                                    ? 'bg-secondary'
                                    : (str_starts_with($row['vinculo_estado'], 'Conflicto') ? 'bg-danger' : 'bg-success');
                            @endphp
                            <span class="badge {{ $vinculoBadge }}">{{ $row['vinculo_estado'] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(count($preview['rows']) > count($preview['sample']))
            <p class="small text-muted">Se muestran los primeros {{ count($preview['sample']) }} registros; se validaron todos.</p>
        @endif

        <form action="{{ route('admin.students.tutors-import.commit') }}" method="POST"
              onsubmit="return confirm('¿Confirmar la vinculación de tutores? Los estudiantes NO serán modificados.');">
            @csrf
            <button class="btn btn-success" type="submit">
                <i class="bi bi-link-45deg me-1"></i>
                Confirmar {{ $preview['links_new'] }} vínculo(s) nuevo(s)
            </button>
        </form>
    @else
        <div class="alert alert-warning">El archivo no contiene relaciones válidas para procesar.</div>
    @endif
</div>
@endif
@endsection
