@extends('layouts.app')

@section('header')
<div class="d-flex align-items-center justify-content-between">
  <h2 class="h4 mb-0"><i class="bi bi-filetype-csv text-success me-2"></i> Importación de estudiantes</h2>
  <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.students.index') }}">Volver a estudiantes</a>
</div>
@endsection

@section('content')
<div class="card card-custom bg-white p-4 mb-4">
  <h5>1. Descargar y completar la plantilla</h5>
  <p class="text-muted mb-3">CSV UTF-8 (coma o punto y coma), máximo 2 MB y 2,000 estudiantes por lote. <strong>En esta etapa no se crean ni se vinculan tutores.</strong></p>

  <div class="row g-3 small mb-3">
    <div class="col-lg-6">
      <div class="border rounded p-3 h-100">
        <strong>Matrícula / número de cuenta</strong>
        <p class="mb-1 mt-2">La columna <code>matricula</code> es opcional.</p>
        <p class="mb-0">Si queda vacía, se genera automáticamente como <code>AÑO-GRUPO-CONSECUTIVO</code>. Ejemplo para Grupo 1-3: <code>{{ now()->year }}-1-3-001</code>, <code>{{ now()->year }}-1-3-002</code>...</p>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="border rounded p-3 h-100">
        <strong>Datos del estudiante</strong>
        <p class="mb-1 mt-2">Obligatorios: nombre, apellido paterno y grupo.</p>
        <p class="mb-0">Opcionales: matrícula manual, apellido materno, fecha de nacimiento, correo, teléfono y estado activo.</p>
      </div>
    </div>
  </div>

  <a href="{{ route('admin.students.import.template') }}" class="btn btn-outline-success btn-sm mb-3">
    <i class="bi bi-download"></i> Descargar plantilla CSV de estudiantes
  </a>

  <div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    El grupo puede escribirse como su código corto, por ejemplo <code>1-3</code>, o como <code>Grupo 1-3 (Turno Matutino)</code>. Si cargas temporalmente la plantilla integral anterior, las columnas de tutor serán ignoradas y <strong>no se guardará información de tutores</strong>.
  </div>

  <p class="small text-muted mb-2">Fechas: <code>AAAA-MM-DD</code> o <code>DD/MM/AAAA</code>. Teléfonos: 10 dígitos o con prefijo +52. <code>estudiante_activo</code>: 1/0 o sí/no (en blanco = activo). El UUID de la credencial QR se genera automáticamente.</p>

  <form action="{{ route('admin.students.import.preview') }}" enctype="multipart/form-data" method="POST">
    @csrf
    <label for="archivo" class="form-label fw-bold">2. Seleccionar CSV</label>
    <input id="archivo" name="archivo" type="file" class="form-control mb-3" accept=".csv,text/csv" required>
    <button class="btn btn-primary" type="submit">Validar y mostrar vista previa</button>
  </form>
</div>

@if($preview)
<div class="card card-custom bg-white p-4">
  <h5>Validación de la carga</h5>
  <p class="mb-2">
    Filas: <strong>{{ $preview['count'] }}</strong> ·
    Válidas: <strong>{{ count($preview['rows']) }}</strong> ·
    Matrículas automáticas: <strong>{{ $preview['automatic_count'] ?? 0 }}</strong> ·
    Errores: <strong>{{ $preview['total_errors'] }}</strong>.
  </p>

  @if($preview['ignored_tutor_columns'] ?? false)
    <div class="alert alert-warning small">
      El archivo contiene columnas de tutor de la plantilla anterior. Fueron ignoradas; esta importación sólo registrará estudiantes.
    </div>
  @endif

  @if($preview['errors'])
    <div class="alert alert-danger">
      <strong>No se importó ningún registro.</strong> Corrija el archivo y vuelva a cargarlo.
      <ul class="mt-2 mb-0">
        @foreach($preview['errors'] as $error)<li>{{ $error }}</li>@endforeach
      </ul>
      @if($preview['total_errors'] > count($preview['errors']))
        <small>Se muestran los primeros 50 errores.</small>
      @endif
    </div>
  @elseif(count($preview['rows']))
    <div class="alert alert-success">Validación satisfactoria. Revise especialmente las matrículas generadas antes de confirmar.</div>

    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th>Matrícula</th>
            <th>Origen</th>
            <th>Estudiante</th>
            <th>Grupo</th>
            <th>Nacimiento</th>
          </tr>
        </thead>
        <tbody>
          @foreach($preview['sample'] as $row)
          <tr>
            <td class="font-monospace fw-semibold">{{ $row['matricula'] }}</td>
            <td>
              <span class="badge {{ $row['matricula_automatica'] ? 'bg-success' : 'bg-secondary' }}">
                {{ $row['matricula_automatica'] ? 'Automática' : 'Manual' }}
              </span>
            </td>
            <td>{{ trim($row['nombre'].' '.$row['apellido_paterno'].' '.($row['apellido_materno'] ?? '')) }}</td>
            <td>{{ $row['grupo'] }}</td>
            <td>{{ $row['fecha_nacimiento'] ?: '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if(count($preview['rows']) > count($preview['sample']))
      <p class="small text-muted">Se muestran los primeros {{ count($preview['sample']) }} registros; se validaron todos.</p>
    @endif

    <form action="{{ route('admin.students.import.commit') }}" method="POST" onsubmit="return confirm('¿Confirmar alta de {{ count($preview['rows']) }} estudiantes? No se crearán tutores.');">
      @csrf
      <button class="btn btn-success" type="submit"><i class="bi bi-check-circle"></i> Confirmar {{ count($preview['rows']) }} estudiantes</button>
    </form>
  @else
    <div class="alert alert-warning">El archivo no contiene registros para importar.</div>
  @endif
</div>
@endif
@endsection
