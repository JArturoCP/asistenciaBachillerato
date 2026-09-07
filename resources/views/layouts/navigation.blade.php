<nav class="navbar navbar-dark navbar-custom py-3 shadow">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-white d-flex align-items-center me-3" href="{{ route('dashboard') }}">
            <i class="bi bi-qr-code-scan me-2 text-warning fs-3"></i>
            <span class="fs-5">SIGO<span class="text-warning">.ControlAsistencias</span></span>
        </a>

        @php
            $u = Auth::user();
            $isSuper = $u?->isSuperAdmin();
            $isAdmin = $u?->role === 'admin';
            $isTeacher = $u?->role === 'teacher' || $u?->role === 'docente';
            $isParent = $u?->role === 'parent' || $u?->role === 'tutor';
            $canViewOverview = $u?->canViewAllStudentAttendance() || $u?->canViewAllTeacherAttendance();
        @endphp

        <!-- Navigation Links -->
        <div class="d-flex align-items-center flex-wrap gap-2 my-1">
            
            {{-- Dashboard (Superadmin & Admin) --}}
            @if($isSuper || $isAdmin)
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('dashboard') ? 'bg-primary fw-bold text-white' : 'hover-opacity' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            @endif

            {{-- Control General de Asistencias (Supervisor, Director, Subdirector, Orientador, Pedagogo, Secretario Escolar, Admin, Superadmin) --}}
            @if($canViewOverview)
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('attendance.overview.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('attendance.overview.index') }}">
                    <i class="bi bi-person-check-fill me-1"></i> Control Asistencias
                </a>
            @endif

            {{-- Escaneo Kiosco (Visible to everyone EXCEPT Parents) --}}
            @if(!$isParent)
                <a class="nav-link text-warning fw-bold px-2 py-1 rounded border border-warning {{ request()->routeIs('scan.index') ? 'bg-warning text-dark' : '' }}" href="{{ route('scan.index') }}">
                    <i class="bi bi-qr-code-scan me-1"></i> Escaneo Kiosco
                </a>
            @endif

            {{-- Reporte Docente (Teacher & Superadmin) --}}
            @if($isTeacher || $isSuper)
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('teacher.attendance.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('teacher.attendance.index') }}">
                    <i class="bi bi-journal-check me-1"></i> Reporte Docente
                </a>
            @endif

            {{-- Portal Padres (Parent & Superadmin) --}}
            @if($isParent || $isSuper)
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('parent.dashboard') ? 'bg-primary fw-bold' : '' }}" href="{{ route('parent.dashboard') }}">
                    <i class="bi bi-house-heart me-1"></i> Portal Padres
                </a>
            @endif

            {{-- Admin Modules (Dashboard, Grupos, Estudiantes, Docentes, Padres y Consentimientos, Solicitudes) --}}
            @if($isAdmin || $isSuper)
                @php
                    $pendingCount = \App\Models\User::pending()->count();
                @endphp
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.pending-registrations.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.pending-registrations.index') }}">
                    <i class="bi bi-person-clock me-1"></i> Solicitudes
                    @if($pendingCount > 0)
                        <span class="badge bg-danger rounded-pill ms-1">{{ $pendingCount }}</span>
                    @endif
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.groups.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.groups.index') }}">
                    <i class="bi bi-diagram-3 me-1"></i> Grupos
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.students.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.students.index') }}">
                    <i class="bi bi-people me-1"></i> Estudiantes
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.teachers.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.teachers.index') }}">
                    <i class="bi bi-journal-text me-1"></i> Docentes
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.teacher-attendance.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.teacher-attendance.index') }}">
                    <i class="bi bi-person-check me-1"></i> Asistencia Docentes
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.guardians.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.guardians.index') }}">
                    <i class="bi bi-shield-check me-1"></i> Padres y Consentimientos
                </a>
            @endif
        </div>

        <!-- User Info & LOGOUT BUTTON -->
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <div class="fw-bold text-white small">{{ Auth::user()->name }}</div>
                <span class="badge {{ $isSuper ? 'bg-warning text-dark' : 'bg-danger text-white' }} border border-light">
                    ROL: {{ strtoupper(str_replace('_', ' ', Auth::user()->role)) }}
                </span>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-danger text-white fw-bold px-3 py-2 shadow border border-2 border-white d-flex align-items-center gap-2" style="background-color: #dc3545 !important; opacity: 1 !important; visibility: visible !important;">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                    <span class="fs-6">Cerrar Sesión</span>
                </button>
            </form>
        </div>

    </div>
</nav>
