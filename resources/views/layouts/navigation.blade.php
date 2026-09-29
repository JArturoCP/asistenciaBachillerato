<nav class="navbar navbar-dark navbar-custom py-3 shadow"><div class="container-fluid px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
<a class="navbar-brand fw-bold text-white d-flex align-items-center me-3" href="{{ route('dashboard') }}"><i class="bi bi-qr-code-scan me-2 text-warning fs-3"></i><span class="fs-5">asistenciaBachillerato<span class="text-warning">.ControlAsistencias</span></span></a>
<div class="d-flex align-items-center flex-wrap gap-2 my-1">
@if(auth()->user()->isAdmin())<a class="nav-link text-white px-2" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>@endif
@if(auth()->user()->canViewAllStudentAttendance() || auth()->user()->canViewAllTeacherAttendance())<a class="nav-link text-white px-2" href="{{ route('attendance.overview.index') }}"><i class="bi bi-person-check-fill"></i> Control Asistencias</a>@endif
@can('scan.use')<a class="nav-link text-warning px-2 fw-bold" href="{{ route('scan.index') }}"><i class="bi bi-qr-code-scan"></i> Escaneo</a>@endcan
@can('teacher.attendance.view')<a class="nav-link text-white px-2" href="{{ route('teacher.attendance.index') }}">Reporte docente</a>@endcan
@can('parent.portal')<a class="nav-link text-white px-2" href="{{ route('parent.dashboard') }}">Portal tutores</a>@endcan
@can('registrations.view')<a class="nav-link text-white px-2" href="{{ route('admin.pending-registrations.index') }}">Solicitudes</a>@endcan
@can('groups.view')<a class="nav-link text-white px-2" href="{{ route('admin.groups.index') }}">Grupos</a>@endcan
@can('students.view')<a class="nav-link text-white px-2" href="{{ route('admin.students.index') }}">Estudiantes</a>@endcan
@can('students.import')<a class="nav-link text-white px-2" href="{{ route('admin.students.import.index') }}">Importar CSV</a>@endcan
@can('teachers.view')<a class="nav-link text-white px-2" href="{{ route('admin.teachers.index') }}">Docentes</a>@endcan
@can('attendance.teachers.view')<a class="nav-link text-white px-2" href="{{ route('admin.teacher-attendance.index') }}">Asistencia docentes</a>@endcan
@can('guardians.view')<a class="nav-link text-white px-2" href="{{ route('admin.guardians.index') }}">Tutores</a>@endcan
@can('whatsapp.manage')<a class="nav-link text-white px-2" href="{{ route('admin.whatsapp.index') }}"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endcan
@can('users.manage')<a class="nav-link text-white px-2" href="{{ route('admin.users.index') }}"><i class="bi bi-person-gear"></i> Usuarios</a>@endcan
@can('roles.manage')<a class="nav-link text-white px-2" href="{{ route('admin.roles.index') }}"><i class="bi bi-shield-lock"></i> Roles y permisos</a>@endcan
</div><div class="d-flex align-items-center gap-3"><div class="text-end d-none d-sm-block"><div class="fw-bold text-white small">{{ auth()->user()->name }}</div><span class="badge bg-primary border border-light">{{ auth()->user()->accessRole?->name ?? auth()->user()->role }}</span></div><form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-danger text-white fw-bold btn-sm" type="submit">Cerrar sesión</button></form></div>
</div></nav>
