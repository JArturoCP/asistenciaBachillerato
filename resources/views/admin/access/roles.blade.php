@extends('layouts.app')
@section('header')<h2 class="h4 mb-0"><i class="bi bi-shield-lock text-primary me-2"></i> Roles y permisos</h2>@endsection
@section('content')
<div class="alert alert-info small">Los roles del sistema se conservan sin alteraciones. Los roles personalizados pueden configurarse con permisos por acción. Los permisos también se validan en el servidor; ocultar botones no concede acceso.</div>
<div class="card card-custom p-4 bg-white mb-4"><h5>Crear rol personalizado</h5>
<form action="{{ route('admin.roles.store') }}" method="POST">@csrf
<div class="row g-3 mb-3"><div class="col-md-6"><label>Nombre visible</label><input class="form-control" name="name" maxlength="100" required></div><div class="col-md-6"><label>Clave técnica (ej. auxiliar_escolar)</label><input class="form-control" name="slug" maxlength="30" pattern="[a-z][a-z0-9_]*" required></div></div>
@include('admin.access.permissions', ['selected'=>[],'prefix'=>'new'])
<button class="btn btn-primary mt-3">Crear rol</button></form></div>
@foreach($roles as $role)
<div class="card card-custom bg-white p-4 mb-3"><div class="d-flex align-items-center justify-content-between mb-2"><div><h5 class="mb-0">{{ $role->name }} <code class="small">{{ $role->slug }}</code></h5><small class="text-muted">{{ $role->permissions->count() }} permisos</small></div><span class="badge {{ $role->is_system ? 'bg-secondary' : 'bg-primary' }}">{{ $role->is_system ? 'Rol base protegido':'Personalizado' }}</span></div>
@if($role->is_system)<p class="small text-muted mb-0">Su definición actual está protegida. {{ $role->permissions->pluck('name')->join(', ') }}</p>
@else
<form action="{{ route('admin.roles.update',$role) }}" method="POST">@csrf @method('PUT')<div class="row g-3 mb-3"><div class="col-md-6"><label>Nombre</label><input class="form-control" name="name" value="{{ $role->name }}" required></div><div class="col-md-6"><label>Clave</label><input class="form-control" name="slug" value="{{ $role->slug }}" required pattern="[a-z][a-z0-9_]*"></div></div>
@include('admin.access.permissions', ['selected'=>$role->permissions->pluck('code')->all(),'prefix'=>'role-'.$role->id])
<button class="btn btn-primary btn-sm mt-3">Guardar permisos</button></form>
<form action="{{ route('admin.roles.destroy',$role) }}" method="POST" class="mt-2">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar este rol? Sólo se permite si no tiene usuarios asignados.')">Eliminar rol</button></form>
@endif</div>
@endforeach
@endsection
