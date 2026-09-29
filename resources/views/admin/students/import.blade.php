@extends('layouts.app')
@section('header')
<div class="d-flex align-items-center justify-content-between">
  <h2 class="h4 mb-0"><i class="bi bi-filetype-csv text-success me-2"></i> Importación integral: estudiantes y tutores</h2>
  <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.students.index') }}">Volver a estudiantes</a>
</div>
@endsection
@section('content')
<div class="card card-custom bg-white p-4 mb-4">
  <h5>1. Descargar y completar la plantilla</h5>
  <p class="text-muted mb-2">CSV UTF-8 (coma o punto y coma), máximo 2 MB y 2,000 estudiantes por lote. Cada fila contiene un estudiante y su tutor; <strong>no modifica estudiantes ya registrados</strong>.</p>
  <div class="row g-3 small mb-3">
    <div class="col-lg-6">
      <div class="border rounded p-3 h-100">
        <strong>Datos del estudiante</strong>
        <p class="mb-1 mt-2">Obligatorios: número de cuenta (<code>matricula</code>), nombre, apellido paterno y código de grupo existente.</p>
        <p class="mb-0">Opcionales: apellido materno, fecha de nacimiento, correo, teléfono y estado activo. La matrícula se procesa como texto, respetando ceros iniciales.</p>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="border rounded p-3 h-100">
        <strong>Datos del padre o tutor</strong>
        <p class="mb-1 mt-2">Obligatorios: nombre, apellido paterno, parentesco (<code>padre</code>, <code>madre</code> o <code>tutor_legal</code>), y <strong>correo o teléfono</strong>.</p>
        <p class="mb-0">Opcionales: apellido materno y correo de notificaciones. Repite el mismo tutor en cada fila de sus hijos: el sistema lo reutiliza automáticamente.</p>
      </div>
    </div>
  </div>
  <a href="{{ route('admin.students.import.template') }}" class="btn btn-outline-success btn-sm mb-3"><i class="bi bi-download"></i> Descargar plantilla integral CSV</a>
  <div class="alert alert-warning small mb-3"><i class="bi bi-shield-lock me-1"></i><strong>Privacidad:</strong> se crea el vínculo alumno–tutor, pero el consentimiento LFPDPPP queda pendiente. Las alertas no se activan al importar. Después deberá registrarse la aceptación en el módulo de tutores; no se asume por aparecer en una hoja de cálculo.</div>
  <p class="small text-muted mb-2">Fechas: <code>AAAA-MM-DD</code> o <code>DD/MM/AAAA</code>. Teléfonos: 10 dígitos o con prefijo +52. <code>estudiante_activo</code>: 1/0 o sí/no (en blanco = activo). No se importa fotografía ni contraseña; el QR se genera automáticamente. Los tutores nuevos con correo pueden establecer su acceso al portal mediante recuperación de contraseña cuando corresponda.</p>
  <form action="{{ route('admin.students.import.preview') }}" enctype="multipart/form-data" method="POST">
    @csrf
    <label for="archivo" class="form-label fw-bold">2. Seleccionar CSV</label>
    <input id="archivo" name="archivo" type="file" class="form-control mb-3" accept=".csv,text/csv" required>
    <button class="btn btn-primary" type="submit">Validar y mostrar vista previa</button>
  </form>
</div>
@if($preview)
<div class="card card-custom bg-white p-4">
  <h5>Validación de la carga integral</h5>
  <p class="mb-2">Filas: <strong>{{ $preview['count'] }}</strong> · Estudiantes válidos: <strong>{{ count($preview['rows']) }}</strong> · Tutores por crear: <strong>{{ $preview['tutors_new'] ?? 0 }}</strong> · Tutores existentes reutilizados: <strong>{{ $preview['tutors_existing'] ?? 0 }}</strong> · Errores: <strong>{{ $preview['total_errors'] }}</strong>.</p>
  @if($preview['errors'])
    <div class="alert alert-danger"><strong>No se importó ningún registro.</strong> Corrija el archivo y vuelva a cargarlo.
      <ul class="mt-2 mb-0">@foreach($preview['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
      @if($preview['total_errors'] > count($preview['errors']))<small>Se muestran los primeros 50 errores.</small>@endif
    </div>
  @elseif(count($preview['rows']))
    <div class="alert alert-success">Validación satisfactoria. Revise la relación estudiante–tutor; el sistema volverá a validar contra la base de datos al confirmar.</div>
    <div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Cuenta</th><th>Estudiante</th><th>Grupo</th><th>Tutor vinculado</th><th>Operación de tutor</th></tr></thead><tbody>
      @foreach($preview['sample'] as $row)
      <tr>
        <td class="font-monospace">{{ $row['matricula'] }}</td>
        <td>{{ trim($row['nombre'].' '.$row['apellido_paterno'].' '.$row['apellido_materno']) }}</td>
        <td>{{ $row['grupo'] }}</td>
        <td>{{ $row['tutor_nombre_completo'] }}</td>
        <td><span class="badge {{ $row['tutor_estado'] === 'Se crea tutor' ? 'bg-primary' : 'bg-secondary' }}">{{ $row['tutor_estado'] }}</span></td>
      </tr>
      @endforeach
    </tbody></table></div>
    @if(count($preview['rows']) > count($preview['sample']))<p class="small text-muted">Se muestran los primeros 10 registros; se verificaron todos.</p>@endif
    <form action="{{ route('admin.students.import.commit') }}" method="POST" onsubmit="return confirm('¿Confirmar alta y vinculación de {{ count($preview['rows']) }} estudiantes? El consentimiento quedará pendiente.');">
      @csrf
      <button class="btn btn-success" type="submit"><i class="bi bi-check-circle"></i> Confirmar {{ count($preview['rows']) }} estudiantes y su vinculación</button>
    </form>
  @else
    <div class="alert alert-warning">El archivo no contiene registros para importar.</div>
  @endif
</div>
@endif
@endsection
