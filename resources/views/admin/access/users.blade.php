@extends('layouts.app')
@section('header')
<div class="d-flex justify-content-between align-items-center"><h2 class="h4 mb-0"><i class="bi bi-people-fill text-primary me-2"></i> Usuarios de apoyo e institucionales</h2>
@can('roles.manage')<a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">Roles y permisos</a>@endcan</div>
@endsection
@section('content')
<div class="alert alert-info small">Cada cuenta tiene su propio acceso. Puedes gestionar roles personalizados y perfiles institucionales como dirección, orientación o supervisión; no se modifican estudiantes, tutores, docentes ni administradores desde esta pantalla. Para suspender un acceso usa el interruptor de estado.</div>
<div class="card card-custom p-4 bg-white mb-4"><h5>Nuevo usuario de apoyo</h5>
<form action="{{ route('admin.users.store') }}" method="POST" class="row g-3">@csrf
 <div class="col-md-4"><label class="form-label">Nombre(s)</label><input name="nombre" class="form-control" maxlength="100" required value="{{ old('nombre') }}"></div>
 <div class="col-md-4"><label class="form-label">Apellido paterno</label><input name="apellido_paterno" class="form-control" maxlength="100" required value="{{ old('apellido_paterno') }}"></div>
 <div class="col-md-4"><label class="form-label">Apellido materno</label><input name="apellido_materno" class="form-control" maxlength="100" value="{{ old('apellido_materno') }}"></div>
 <div class="col-md-4"><label class="form-label">Correo de acceso</label><input type="email" name="email" class="form-control" required value="{{ old('email') }}"></div>
 <div class="col-md-3"><label class="form-label">Teléfono (opcional)</label><input name="phone" class="form-control" maxlength="10" value="{{ old('phone') }}"></div>
 <div class="col-md-5"><label class="form-label">Rol asignado</label><select name="role" class="form-select" required><option value="">Seleccione rol</option>@foreach($roles as $role)<option value="{{ $role->slug }}" @selected(old('role')===$role->slug)>{{ $role->name }}</option>@endforeach</select></div>
 <div class="col-md-4"><label class="form-label">Contraseña inicial (mín. 10 caracteres)</label><input type="password" name="password" class="form-control" required autocomplete="new-password"></div>
 <div class="col-md-4"><label class="form-label">Confirmar contraseña</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
 <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100">Crear colaborador</button></div>
</form></div>
<div class="card card-custom p-4 bg-white"><h5>Colaboradores registrados</h5><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($users as $user)
<tr><td>{{ $user->nombre_completo }}</td><td>{{ $user->email }}</td><td><span class="badge bg-primary">{{ $user->accessRole?->name }}</span></td><td><span class="badge {{ $user->is_approved ? 'bg-success':'bg-secondary' }}">{{ $user->is_approved ? 'Habilitado':'Suspendido' }}</span></td><td>
@if($roles->contains('slug',$user->role) && $user->id !== auth()->id())
<button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#edit-{{ $user->id }}">Editar</button>
<form action="{{ route('admin.users.status',$user) }}" method="POST" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="is_approved" value="{{ $user->is_approved ? 0:1 }}"><button class="btn btn-outline-{{ $user->is_approved ? 'warning':'success' }} btn-sm" onclick="return confirm('¿Cambiar estado de acceso?')">{{ $user->is_approved ? 'Suspender':'Habilitar' }}</button></form>
<div class="modal fade" id="edit-{{ $user->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('admin.users.update',$user) }}" class="modal-content">@csrf @method('PUT')<div class="modal-header"><h5>Editar {{ $user->nombre_completo }}</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3">
<div class="col-md-4"><label>Nombre</label><input name="nombre" class="form-control" value="{{ $user->nombre }}" required></div><div class="col-md-4"><label>Apellido paterno</label><input name="apellido_paterno" class="form-control" value="{{ $user->apellido_paterno }}" required></div><div class="col-md-4"><label>Apellido materno</label><input name="apellido_materno" class="form-control" value="{{ $user->apellido_materno }}"></div>
<div class="col-md-6"><label>Correo</label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div><div class="col-md-6"><label>Teléfono</label><input name="phone" class="form-control" value="{{ $user->phone }}"></div>
<div class="col-md-6"><label>Rol</label><select name="role" class="form-select">@foreach($roles as $role)<option value="{{ $role->slug }}" @selected($user->role===$role->slug)>{{ $role->name }}</option>@endforeach</select></div><div class="col-md-6"><label>Nueva contraseña (dejar en blanco para conservar)</label><input type="password" name="password" class="form-control" autocomplete="new-password"></div><div class="col-md-6"><label>Confirmar nueva contraseña</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar cambios</button></div></form></div></div>
@endif</td></tr>
@empty<tr><td colspan="5" class="text-muted text-center">Sin colaboradores registrados.</td></tr>@endforelse
</tbody></table></div>{{ $users->links() }}</div>
@endsection
