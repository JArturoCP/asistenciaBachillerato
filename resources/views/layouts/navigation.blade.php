<nav class="navbar navbar-dark navbar-custom py-3 shadow">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-white d-flex align-items-center me-3" href="{{ route('dashboard') }}">
            <i class="bi bi-qr-code-scan me-2 text-warning fs-3"></i>
            <span class="fs-5">ControlAsistencia<span class="text-warning">.Bachillerato</span></span>
        </a>

        <!-- Navigation Links (Always visible flex container) -->
        <div class="d-flex align-items-center flex-wrap gap-3 my-1">
            <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('dashboard') ? 'bg-primary fw-bold text-white' : 'hover-opacity' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>

            <a class="nav-link text-warning fw-bold px-2 py-1 rounded border border-warning {{ request()->routeIs('scan.index') ? 'bg-warning text-dark' : '' }}" href="{{ route('scan.index') }}">
                <i class="bi bi-qr-code-scan me-1"></i> Escaneo Kiosco
            </a>

            @if(Auth::check() && (Auth::user()->isTeacher() || Auth::user()->isAdmin()))
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('teacher.attendance.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('teacher.attendance.index') }}">
                    <i class="bi bi-journal-check me-1"></i> Reporte Docente
                </a>
            @endif

            @if(Auth::check() && (Auth::user()->isParent() || Auth::user()->isAdmin()))
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('parent.dashboard') ? 'bg-primary fw-bold' : '' }}" href="{{ route('parent.dashboard') }}">
                    <i class="bi bi-house-heart me-1"></i> Portal Padres
                </a>
            @endif

            @if(Auth::check() && Auth::user()->isAdmin())
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.groups.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.groups.index') }}">
                    <i class="bi bi-diagram-3 me-1"></i> Grupos
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.students.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.students.index') }}">
                    <i class="bi bi-people me-1"></i> Estudiantes
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.teachers.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.teachers.index') }}">
                    <i class="bi bi-journal-text me-1"></i> Docentes
                </a>
                <a class="nav-link text-white px-2 py-1 rounded {{ request()->routeIs('admin.guardians.*') ? 'bg-primary fw-bold' : '' }}" href="{{ route('admin.guardians.index') }}">
                    <i class="bi bi-shield-check me-1"></i> Padres y Consentimientos
                </a>
            @endif
        </div>

        <!-- User Info & LOGOUT BUTTON (ALWAYS VISIBLE WITH HIGH CONTRAST RED BUTTON) -->
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <div class="fw-bold text-white small">{{ Auth::user()->name }}</div>
                <span class="badge bg-danger text-white border border-light">ROL: {{ strtoupper(Auth::user()->role) }}</span>
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
